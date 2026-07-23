<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\RenderCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SettingController extends Controller
{
    private const COLOR_FIELDS = [
        'color_primary' => ['--g', '#2b8a99', 'Warna utama (hero, tombol, judul)'],
        'color_primary_dark' => ['--gd', '#1f6a76', 'Warna utama gelap (gradient & hover)'],
        'color_accent' => ['--gold', '#f5a623', 'Warna aksen (angka, sorotan)'],
        'color_cta' => ['--red', '#e8543f', 'Warna tombol sekunder'],
        'color_bg' => ['--bg', '#f2fbfc', 'Latar halaman'],
        'color_ink' => ['--ink', '#173d44', 'Warna teks utama'],
    ];

    private const KEYS = [
        'brand_name', 'tagline', 'theme_prefix', 'whatsapp_number', 'whatsapp_message',
        'logo_image',
        'image_solusi', 'image_keunggulan', 'image_tentang',
        'home_stat1_num', 'home_stat1_label', 'home_stat2_num', 'home_stat2_label', 'home_stat3_num', 'home_stat3_label',
        'home_gallery1', 'home_gallery2', 'home_gallery3', 'home_gallery4', 'home_gallery5', 'home_gallery6',
        'home_owner_img', 'home_owner_name', 'home_owner_role', 'home_owner_desc',
        'home_schools_img',
        'contact_address', 'contact_phone', 'contact_email', 'contact_hours',
        'organization_name', 'organization_url', 'organization_logo',
        'hero_image', 'template_blade_enabled',
        // Integrasi Google/Bing
        'google_site_verification', 'bing_site_verification',
        'google_analytics_id', 'gtm_id',
        // Palet warna per instalasi (mesin dipakai lintas produk)
        'color_primary', 'color_primary_dark', 'color_accent', 'color_cta', 'color_bg', 'color_ink',
        // Slot gambar tambahan agar salespage lebih kaya visual
        'image_proses', 'image_galeri_1', 'image_galeri_2', 'image_galeri_3',
        'image_galeri_4', 'image_galeri_5', 'image_galeri_6',
    ];

    public function edit()
    {
        // #region debug-point A:settings-edit-entry
        $this->reportDebug('A', 'pre-fix', '[DEBUG] Enter admin.settings.edit', [
            'route' => request()->path(),
            'user_id' => optional(request()->user())->id,
        ]);
        // #endregion
        try {
            $settings = Setting::map();
            $colorPalette = $this->colorPalette();
            $colorValues = [];
            foreach ($colorPalette as $key => $info) {
                $colorValues[$key] = $this->currentColor($key, $settings[$key] ?? null, $info[1]);
            }
            // #region debug-point B:settings-map-loaded
            $this->reportDebug('B', 'pre-fix', '[DEBUG] Loaded settings map', [
                'count' => is_array($settings) ? count($settings) : null,
                'has_brand_name' => is_array($settings) && array_key_exists('brand_name', $settings),
                'has_theme_prefix' => is_array($settings) && array_key_exists('theme_prefix', $settings),
                'color_palette_count' => count($colorPalette),
            ]);
            // #endregion

            return view('admin.settings.edit', compact('settings', 'colorPalette', 'colorValues'));
        } catch (\Throwable $e) {
            // #region debug-point C:settings-edit-exception
            $this->reportDebug('C', 'pre-fix', '[DEBUG] Exception in admin.settings.edit', [
                'class' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            // #endregion
            throw $e;
        }
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'brand_name' => ['nullable', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:200'],
            'theme_prefix' => ['nullable', 'string', 'regex:/^[a-z][a-z0-9]{1,7}$/'],
            'image_solusi' => ['nullable', 'string', 'max:500'],
            'image_keunggulan' => ['nullable', 'string', 'max:500'],
            'image_tentang' => ['nullable', 'string', 'max:500'],
            'home_stat1_num' => ['nullable', 'string', 'max:40'],
            'home_stat1_label' => ['nullable', 'string', 'max:60'],
            'home_stat2_num' => ['nullable', 'string', 'max:40'],
            'home_stat2_label' => ['nullable', 'string', 'max:60'],
            'home_stat3_num' => ['nullable', 'string', 'max:40'],
            'home_stat3_label' => ['nullable', 'string', 'max:60'],
            'home_gallery1' => ['nullable', 'string', 'max:500'],
            'home_gallery2' => ['nullable', 'string', 'max:500'],
            'home_gallery3' => ['nullable', 'string', 'max:500'],
            'home_gallery4' => ['nullable', 'string', 'max:500'],
            'home_gallery5' => ['nullable', 'string', 'max:500'],
            'home_gallery6' => ['nullable', 'string', 'max:500'],
            'home_owner_img' => ['nullable', 'string', 'max:500'],
            'home_owner_name' => ['nullable', 'string', 'max:80'],
            'home_owner_role' => ['nullable', 'string', 'max:80'],
            'home_owner_desc' => ['nullable', 'string', 'max:600'],
            'home_schools_img' => ['nullable', 'string', 'max:500'],
            'contact_address' => ['nullable', 'string', 'max:300'],
            'contact_phone' => ['nullable', 'string', 'max:60'],
            'contact_email' => ['nullable', 'string', 'max:120'],
            'contact_hours' => ['nullable', 'string', 'max:120'],
            'whatsapp_number' => ['nullable', 'string', 'max:500'],
            'logo_image' => ['nullable', 'string', 'max:500'],
            'whatsapp_message' => ['nullable', 'string', 'max:500'],
            'organization_name' => ['nullable', 'string', 'max:120'],
            'organization_url' => ['nullable', 'url', 'max:255'],
            'organization_logo' => ['nullable', 'url', 'max:255'],
            // Boleh URL penuh atau path relatif (mis. /images/hero.jpg).
            'hero_image' => ['nullable', 'string', 'max:500'],
            // Integrasi Google/Bing (boleh tempel tag <meta> penuh; dibersihkan di bawah).
            'google_site_verification' => ['nullable', 'string', 'max:500'],
            'bing_site_verification' => ['nullable', 'string', 'max:500'],
            'google_analytics_id' => ['nullable', 'string', 'max:40'],
            'gtm_id' => ['nullable', 'string', 'max:40'],
            'color_primary' => ['nullable', 'string', 'max:10'],
            'color_primary_dark' => ['nullable', 'string', 'max:10'],
            'color_accent' => ['nullable', 'string', 'max:10'],
            'color_cta' => ['nullable', 'string', 'max:10'],
            'color_bg' => ['nullable', 'string', 'max:10'],
            'color_ink' => ['nullable', 'string', 'max:10'],
            'image_proses' => ['nullable', 'string', 'max:500'],
            'image_galeri_1' => ['nullable', 'string', 'max:500'],
            'image_galeri_2' => ['nullable', 'string', 'max:500'],
            'image_galeri_3' => ['nullable', 'string', 'max:500'],
            'image_galeri_4' => ['nullable', 'string', 'max:500'],
            'image_galeri_5' => ['nullable', 'string', 'max:500'],
            'image_galeri_6' => ['nullable', 'string', 'max:500'],
        ]);

        // Warna: hanya terima kode HEX yang sah (cegah penyuntikan CSS).
        foreach (array_keys($this->colorPalette()) as $ck) {
            if (array_key_exists($ck, $data)) {
                $data[$ck] = $this->sanitizeColor((string) $data[$ck]);
            }
        }

        // Bersihkan input verifikasi: bila admin menempel seluruh tag
        // <meta ... content="KODE">, ambil KODE-nya saja.
        $data['google_site_verification'] = $this->extractVerification($data['google_site_verification'] ?? null);
        $data['bing_site_verification'] = $this->extractVerification($data['bing_site_verification'] ?? null);
        // ID GA4/GTM: buang spasi & karakter aneh.
        $data['google_analytics_id'] = $this->cleanId($data['google_analytics_id'] ?? null);
        $data['gtm_id'] = $this->cleanId($data['gtm_id'] ?? null);

        foreach (self::KEYS as $key) {
            if ($key === 'template_blade_enabled') {
                Setting::put($key, $request->boolean('template_blade_enabled') ? '1' : '0');

                continue;
            }
            Setting::put($key, $data[$key] ?? null);
        }

        RenderCache::bump();

        return back()->with('status', 'Pengaturan disimpan.');
    }

    /**
     * Ambil kode verifikasi. Bila admin menempel seluruh tag
     * <meta name="..." content="KODE">, kembalikan KODE saja; jika sudah berupa
     * kode polos, kembalikan apa adanya (di-trim).
     */
    private function extractVerification(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        if (preg_match('/content\s*=\s*["\']([^"\']+)["\']/i', $raw, $m)) {
            return trim($m[1]);
        }

        return $raw;
    }

    /**
     * Bersihkan ID GA4/GTM: hanya izinkan huruf, angka, dan tanda hubung.
     */
    private function cleanId(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        return preg_replace('/[^A-Za-z0-9\-]/', '', $raw) ?: null;
    }

    private function colorPalette(): array
    {
        if (class_exists(\App\Support\BrandColors::class) && defined(\App\Support\BrandColors::class . '::MAP')) {
            /** @var array<string, array{0:string,1:string,2:string}> $map */
            $map = \App\Support\BrandColors::MAP;

            return $map;
        }

        return self::COLOR_FIELDS;
    }

    private function sanitizeColor(?string $value): ?string
    {
        if (class_exists(\App\Support\BrandColors::class)) {
            return \App\Support\BrandColors::hex((string) $value);
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (! str_starts_with($value, '#')) {
            $value = '#' . $value;
        }

        return preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) === 1 ? $value : null;
    }

    private function currentColor(string $key, ?string $storedValue, string $default): string
    {
        if (class_exists(\App\Support\BrandColors::class)) {
            return \App\Support\BrandColors::current($key);
        }

        return $this->sanitizeColor($storedValue) ?? $default;
    }

    private function reportDebug(string $hypothesisId, string $runId, string $message, array $data = []): void
    {
        $envPath = base_path('.dbg/admin-settings-500.env');
        $url = 'http://127.0.0.1:7777/event';
        $sessionId = 'admin-settings-500';

        if (is_file($envPath)) {
            foreach (preg_split("/\r\n|\n|\r/", (string) @file_get_contents($envPath)) as $line) {
                if (str_starts_with($line, 'DEBUG_SERVER_URL=')) {
                    $url = trim(substr($line, 17));
                }
                if (str_starts_with($line, 'DEBUG_SESSION_ID=')) {
                    $sessionId = trim(substr($line, 17));
                }
            }
        }

        try {
            Http::timeout(1)->post($url, [
                'sessionId' => $sessionId,
                'runId' => $runId,
                'hypothesisId' => $hypothesisId,
                'location' => 'SettingController',
                'msg' => $message,
                'data' => $data,
                'ts' => (int) round(microtime(true) * 1000),
            ]);
        } catch (\Throwable) {
            // Abaikan kegagalan debug collector agar alur aplikasi tetap normal.
        }
    }
}
