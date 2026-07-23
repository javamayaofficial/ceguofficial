<?php

namespace App\Services\SearchConsole;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Menukar kredensial SERVICE ACCOUNT Google menjadi access token OAuth2, tanpa
 * paket google/apiclient. Alur standar "JWT bearer":
 *   1. Susun JWT (header+claim) yang di-sign RS256 dengan private key SA.
 *   2. POST ke token endpoint → dapat access_token (berlaku ~1 jam).
 * Token di-cache agar tidak menandatangani ulang tiap request.
 *
 * openssl_sign() sudah tersedia di PHP standar → tidak butuh dependency.
 */
class GoogleServiceAccountToken
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const CACHE_KEY = 'gsc:access_token';

    public function __construct(
        private readonly string $scope = 'https://www.googleapis.com/auth/webmasters.readonly',
    ) {
    }

    /**
     * @return array{client_email:string, private_key:string} Kredensial ter-decode.
     */
    private function credentials(): array
    {
        $raw = (string) config('services.gsc.credentials', '');
        if (trim($raw) === '') {
            throw new RuntimeException('GSC_CREDENTIALS belum diisi (path file JSON atau JSON mentah).');
        }

        // Boleh berupa path file atau JSON mentah.
        if (! str_starts_with(trim($raw), '{') && is_file($raw)) {
            $raw = (string) file_get_contents($raw);
        }

        $data = json_decode($raw, true);
        if (! is_array($data) || empty($data['client_email']) || empty($data['private_key'])) {
            throw new RuntimeException('Kredensial GSC tidak valid (butuh client_email & private_key).');
        }

        return ['client_email' => $data['client_email'], 'private_key' => $data['private_key']];
    }

    public function accessToken(): string
    {
        $cached = Cache::get(self::CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $creds = $this->credentials();
        $now = time();

        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claim = [
            'iss' => $creds['client_email'],
            'scope' => $this->scope,
            'aud' => self::TOKEN_URL,
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $segments = [$this->b64(json_encode($header)), $this->b64(json_encode($claim))];
        $signingInput = implode('.', $segments);

        $signature = '';
        $ok = openssl_sign($signingInput, $signature, $creds['private_key'], OPENSSL_ALGO_SHA256);
        if (! $ok) {
            throw new RuntimeException('Gagal menandatangani JWT (periksa private_key service account).');
        }
        $jwt = $signingInput . '.' . $this->b64($signature);

        $resp = Http::asForm()->timeout(30)->post(self::TOKEN_URL, [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if ($resp->failed()) {
            throw new RuntimeException('Gagal menukar token Google: ' . mb_substr($resp->body(), 0, 200));
        }

        $token = (string) $resp->json('access_token', '');
        $expires = (int) $resp->json('expires_in', 3600);
        if ($token === '') {
            throw new RuntimeException('Token Google kosong.');
        }

        Cache::put(self::CACHE_KEY, $token, max(60, $expires - 60));

        return $token;
    }

    /** base64url tanpa padding. */
    private function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
