<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;

/**
 * Driver untuk semua endpoint yang KOMPATIBEL OpenAI
 * (POST /chat/completions):
 *   - OpenAI          base_url=https://api.openai.com/v1
 *   - DeepSeek        base_url=https://api.deepseek.com   (murah, populer di ID)
 *   - Groq            base_url=https://api.groq.com/openai/v1
 *   - OpenRouter      base_url=https://openrouter.ai/api/v1
 *   - Together / Mistral / LM lokal (Ollama, LM Studio), dll.
 *
 * Satu implementasi menutup ~90% penyedia. Ganti provider cukup dengan
 * mengubah AI_DRIVER / AI_BASE_URL / AI_MODEL / AI_API_KEY di .env.
 */
class OpenAiCompatibleClient implements AiChatClient
{
    public function __construct(
        private readonly string $key,
        private readonly string $model,
        private readonly string $baseUrl,
        private readonly string $label = 'OpenAI-compatible',
        private readonly int $timeout = 120,
    ) {
    }

    public function chat(string $system, string $user, array $opts = []): array
    {
        if (trim($this->key) === '') {
            throw new AiException('Kunci API AI belum diisi. Isi AI_API_KEY di file .env.');
        }

        $payload = [
            'model' => $this->model,
            'temperature' => $opts['temperature'] ?? 0.9,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
        ];
        if (isset($opts['max_tokens'])) {
            $payload['max_tokens'] = (int) $opts['max_tokens'];
        }

        $endpoint = rtrim($this->baseUrl, '/') . '/chat/completions';

        try {
            $resp = Http::withToken($this->key)
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

        $content = (string) data_get($resp->json(), 'choices.0.message.content', '');
        if (trim($content) === '') {
            throw new AiException('AI mengembalikan jawaban kosong.');
        }

        $tokens = (int) data_get($resp->json(), 'usage.total_tokens', 0);

        return ['content' => $content, 'tokens' => $tokens];
    }

    public function label(): string
    {
        return $this->label;
    }
}
