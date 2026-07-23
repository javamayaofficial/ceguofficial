<?php

namespace App\Services\Ai;

/**
 * Memilih & merakit client AI berdasarkan config/services.php → 'ai'.
 *
 * Preset base_url disediakan untuk driver populer agar admin cukup mengisi
 * AI_DRIVER + AI_API_KEY + AI_MODEL. Untuk endpoint lain (Together, lokal),
 * pakai AI_DRIVER=custom dan isi AI_BASE_URL sendiri.
 */
class AiClientFactory
{
    /** Preset base_url untuk driver OpenAI-compatible yang umum. */
    private const PRESETS = [
        'openai' => 'https://api.openai.com/v1',
        'deepseek' => 'https://api.deepseek.com',
        'groq' => 'https://api.groq.com/openai/v1',
        'openrouter' => 'https://openrouter.ai/api/v1',
    ];

    public static function make(): AiChatClient
    {
        $cfg = (array) config('services.ai', []);
        $driver = strtolower((string) ($cfg['driver'] ?? 'openai'));
        $key = (string) ($cfg['key'] ?? '');
        $model = (string) ($cfg['model'] ?? '');
        $timeout = (int) ($cfg['timeout'] ?? 120);

        if ($driver === 'anthropic') {
            $baseUrl = (string) ($cfg['base_url'] ?: 'https://api.anthropic.com');

            return new AnthropicClient($key, $model ?: 'claude-sonnet-5', $baseUrl, $timeout);
        }

        // Semua driver lain diperlakukan sebagai OpenAI-compatible.
        $baseUrl = (string) ($cfg['base_url'] ?: (self::PRESETS[$driver] ?? self::PRESETS['openai']));
        $label = ucfirst($driver);

        return new OpenAiCompatibleClient($key, $model ?: 'gpt-4o-mini', $baseUrl, $label, $timeout);
    }

    /**
     * Apakah AI sudah dikonfigurasi (kunci terisi)? Dipakai controller untuk
     * mencegah dispatch job saat kunci belum diisi.
     */
    public static function isConfigured(): bool
    {
        return trim((string) config('services.ai.key', '')) !== '';
    }
}
