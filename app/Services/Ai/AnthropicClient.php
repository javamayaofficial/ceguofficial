<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;

/**
 * Driver native Anthropic (Claude) — endpoint /v1/messages.
 *
 * Berbeda format dari OpenAI: pakai header x-api-key + anthropic-version,
 * "system" jadi field tersendiri, dan jawaban ada di content[0].text.
 */
class AnthropicClient implements AiChatClient
{
    private const API_VERSION = '2023-06-01';

    public function __construct(
        private readonly string $key,
        private readonly string $model,
        private readonly string $baseUrl = 'https://api.anthropic.com',
        private readonly int $timeout = 120,
    ) {
    }

    public function chat(string $system, string $user, array $opts = []): array
    {
        if (trim($this->key) === '') {
            throw new AiException('Kunci API AI belum diisi. Isi AI_API_KEY di file .env.');
        }

        // Prompt caching (opsional): bila 'cache' => true, tandai blok system
        // dengan cache_control. Bagian system yang SAMA antar panggilan dihitung
        // sekali, sisanya kena tarif "cache read" yang jauh lebih murah.
        // Efektif untuk asisten yang mengirim aturan + knowledge berulang.
        if (! empty($opts['cache']) && trim($system) !== '') {
            $systemField = [[
                'type' => 'text',
                'text' => $system,
                'cache_control' => ['type' => 'ephemeral'],
            ]];
        } else {
            $systemField = $system;
        }

        $payload = [
            'model' => $this->model,
            'max_tokens' => (int) ($opts['max_tokens'] ?? 4096),
            'temperature' => $opts['temperature'] ?? 0.9,
            'system' => $systemField,
            'messages' => [
                ['role' => 'user', 'content' => $user],
            ],
        ];

        $endpoint = rtrim($this->baseUrl, '/') . '/v1/messages';

        try {
            $resp = Http::withHeaders([
                'x-api-key' => $this->key,
                'anthropic-version' => self::API_VERSION,
            ])
                ->timeout($this->timeout)
                ->acceptJson()
                ->retry(2, 2000, throw: false)
                ->post($endpoint, $payload);
        } catch (\Throwable $e) {
            throw new AiException('Gagal menghubungi server AI: ' . $e->getMessage(), previous: $e);
        }

        if ($resp->failed()) {
            $reason = (string) data_get($resp->json(), 'error.message', $resp->body());
            throw new AiException("AI menolak permintaan (HTTP {$resp->status()}): " . mb_substr($reason, 0, 300));
        }

        $content = (string) data_get($resp->json(), 'content.0.text', '');
        if (trim($content) === '') {
            throw new AiException('AI mengembalikan jawaban kosong.');
        }

        // Total token termasuk cache (untuk pantau biaya).
        $usage = (array) data_get($resp->json(), 'usage', []);
        $tokens = (int) ($usage['input_tokens'] ?? 0)
            + (int) ($usage['output_tokens'] ?? 0)
            + (int) ($usage['cache_read_input_tokens'] ?? 0)
            + (int) ($usage['cache_creation_input_tokens'] ?? 0);

        return ['content' => $content, 'tokens' => $tokens];
    }

    public function label(): string
    {
        return 'Anthropic (Claude)';
    }
}
