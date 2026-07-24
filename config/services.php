<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI (pengisian otomatis pool konten)
    |--------------------------------------------------------------------------
    | driver : openai | deepseek | groq | openrouter | anthropic | custom
    | key    : kunci API dari penyedia
    | model  : nama model (mis. gpt-4o-mini, deepseek-chat, claude-sonnet-4-6)
    | base_url: kosongkan untuk pakai preset driver; isi untuk endpoint lain
    |           (Together, Mistral, atau LLM lokal Ollama/LM Studio).
    */
    'ai' => [
        'driver' => env('AI_DRIVER', 'openai'),
        'key' => env('AI_API_KEY'),
        'model' => env('AI_MODEL', 'gpt-4o-mini'),
        'base_url' => env('AI_BASE_URL', ''),
        'timeout' => (int) env('AI_TIMEOUT', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | IndexNow (indexing kilat: Bing, Yandex, Naver, Seznam)
    |--------------------------------------------------------------------------
    | Isi INDEXNOW_KEY (string hex acak, mis. 32 karakter). Saat halaman
    | dipublish, URL-nya otomatis dikirim ke IndexNow agar cepat terindeks.
    | Kosongkan untuk menonaktifkan (tidak ada pengiriman apa pun).
    | File verifikasi disajikan otomatis di /indexnow.txt.
    */
    'indexnow' => [
        'key' => env('INDEXNOW_KEY', ''),
        'endpoint' => env('INDEXNOW_ENDPOINT', 'https://api.indexnow.org/indexnow'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Search Console API (monitoring indexing/impresi)
    |--------------------------------------------------------------------------
    | Butuh SERVICE ACCOUNT dari Google Cloud (aktifkan "Google Search Console
    | API"), lalu tambahkan email service account itu sebagai pengguna di
    | properti Search Console Anda.
    |
    | credentials : path ke file JSON service account ATAU JSON mentah.
    | site_url    : properti GSC, mis. 'sc-domain:example.com' atau
    |               'https://example.com/'. Kosong = pakai domain aplikasi.
    */
    'status_token' => env('STATUS_TOKEN', ''),

    'gsc' => [
        'credentials' => env('GSC_CREDENTIALS', ''),
        'site_url' => env('GSC_SITE_URL', ''),
    ],

];
