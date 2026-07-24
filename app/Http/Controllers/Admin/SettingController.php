<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\BrandColors;
use App\Support\RenderCache;
use Illuminate\Http\Request;

class SettingController extends Controller
{
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
        'google_site_verification', 'bing_site_verification',
        'google_analytics_id', 'gtm_id',
        'color_primary', 'color_primary_dark', 'color_accent', 'color_cta', 'color_bg', 'color_ink',
        'image_proses', 'image_galeri_1', 'image_galeri_2', 'image_galeri_3',
        'image_galeri_4', 'image_galeri_5', 'image_galeri_6',
        'home_hero_eyebrow', 'home_hero_title', 'home_hero_lead',
        'home_cta_title', 'home_cta_lead', 'home_about_desc',
        'home_testi1', 'home_testi1_who', 'home_testi2', 'home_testi2_who',
        'home_testi3', 'home_testi3_who',
        'home_use_template', 'home_area_label', 'home_layanan_label',
        'kredensial_1_img', 'kredensial_1_label', 'kredensial_2_img', 'kredensial_2_label',
        'kredensial_3_img', 'kredensial_3_label', 'kredensial_4_img', 'kredensial_4_label',
        'kredensial_5_img', 'kredensial_5_label', 'kredensial_6_img', 'kredensial_6_label',
        'engine_name', 'engine_logo',
        'default_robots', 'og_image', 'og_locale', 'theme_color',
        'ai_driver', 'ai_model', 'ai_base_url',
        'referensi_1_label', 'referensi_1_url', 'referensi_2_label', 'referensi_2_url',
        'referensi_3_label', 'referensi_3_url', 'referensi_4_label', 'referensi_4_url',
    ];

    public function edit()
    {
        $settings = Setting::map();
        $colorPalette = BrandColors::MAP;
        $colorValues = [];
        foreach ($colorPalette as $key => $info) {
            $colorValues[$key] = BrandColors::current($key);
        }

        return view('admin.settings.edit', compact('settings', 'colorPalette', 'colorValues'));
    }

    public function testAi()
    {
        if (! \App\Services\Ai\AiClientFactory::isConfigured()) {
            return response()->json(['ok' => false, 'pesan' => 'Kunci API belum diisi.']);
        }

        try {
            $cfg = \App\Services\Ai\AiClientFactory::config();
            $res = \App\Services\Ai\AiClientFactory::make()->chat(
                'Jawab sangat singkat dalam Bahasa Indonesia.',
                'Balas dengan satu kata: OK',
                ['max_tokens' => 20, 'temperature' => 0],
            );

            return response()->json([
                'ok' => true,
                'pesan' => 'Koneksi berhasil.',
                'jawaban' => mb_substr(trim((string) $res['content']), 0, 60),
                'model' => $cfg['model'] ?? '',
                'token' => $res['tokens'] ?? 0,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'pesan' => mb_substr($e->getMessage(), 0, 250)]);
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
            'hero_image' => ['nullable', 'string', 'max:500'],
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
            'home_hero_eyebrow' => ['nullable', 'string', 'max:80'],
            'home_hero_title' => ['nullable', 'string', 'max:190'],
            'home_hero_lead' => ['nullable', 'string', 'max:500'],
            'home_cta_title' => ['nullable', 'string', 'max:190'],
            'home_cta_lead' => ['nullable', 'string', 'max:500'],
            'home_about_desc' => ['nullable', 'string', 'max:2000'],
            'home_testi1' => ['nullable', 'string', 'max:500'],
            'home_testi1_who' => ['nullable', 'string', 'max:120'],
            'home_testi2' => ['nullable', 'string', 'max:500'],
            'home_testi2_who' => ['nullable', 'string', 'max:120'],
            'home_testi3' => ['nullable', 'string', 'max:500'],
            'home_testi3_who' => ['nullable', 'string', 'max:120'],
            'home_area_label' => ['nullable', 'string', 'max:80'],
            'home_layanan_label' => ['nullable', 'string', 'max:80'],
            'kredensial_1_img' => ['nullable', 'string', 'max:500'],
            'kredensial_1_label' => ['nullable', 'string', 'max:120'],
            'kredensial_2_img' => ['nullable', 'string', 'max:500'],
            'kredensial_2_label' => ['nullable', 'string', 'max:120'],
            'kredensial_3_img' => ['nullable', 'string', 'max:500'],
            'kredensial_3_label' => ['nullable', 'string', 'max:120'],
            'kredensial_4_img' => ['nullable', 'string', 'max:500'],
            'kredensial_4_label' => ['nullable', 'string', 'max:120'],
            'kredensial_5_img' => ['nullable', 'string', 'max:500'],
            'kredensial_5_label' => ['nullable', 'string', 'max:120'],
            'kredensial_6_img' => ['nullable', 'string', 'max:500'],
            'kredensial_6_label' => ['nullable', 'string', 'max:120'],
            'engine_name' => ['nullable', 'string', 'max:60'],
            'engine_logo' => ['nullable', 'string', 'max:500'],
            'default_robots' => ['nullable', 'string', 'in:index,follow,noindex,follow,index,nofollow,noindex,nofollow'],
            'og_image' => ['nullable', 'string', 'max:500'],
            'og_locale' => ['nullable', 'string', 'max:10'],
            'theme_color' => ['nullable', 'string', 'max:10'],
            'ai_driver' => ['nullable', 'string', 'max:30'],
            'ai_model' => ['nullable', 'string', 'max:120'],
            'ai_base_url' => ['nullable', 'string', 'max:255'],
            'referensi_1_label' => ['nullable', 'string', 'max:120'],
            'referensi_1_url' => ['nullable', 'url', 'max:400'],
            'referensi_2_label' => ['nullable', 'string', 'max:120'],
            'referensi_2_url' => ['nullable', 'url', 'max:400'],
            'referensi_3_label' => ['nullable', 'string', 'max:120'],
            'referensi_3_url' => ['nullable', 'url', 'max:400'],
            'referensi_4_label' => ['nullable', 'string', 'max:120'],
            'referensi_4_url' => ['nullable', 'url', 'max:400'],
            'ai_api_key' => ['nullable', 'string', 'max:300'],
        ]);

        $kunciBaru = trim((string) $request->input('ai_api_key', ''));
        if ($kunciBaru === '-') {
            \App\Services\Ai\AiClientFactory::simpanKunci('');
        } elseif ($kunciBaru !== '') {
            \App\Services\Ai\AiClientFactory::simpanKunci($kunciBaru);
        }
        unset($data['ai_api_key']);

        $data['home_use_template'] = $request->boolean('home_use_template') ? '1' : null;
        $data['theme_color'] = BrandColors::hex((string) ($data['theme_color'] ?? ''));

        foreach (array_keys(BrandColors::MAP) as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = BrandColors::hex((string) $data[$key]);
            }
        }

        $data['google_site_verification'] = $this->extractVerification($data['google_site_verification'] ?? null);
        $data['bing_site_verification'] = $this->extractVerification($data['bing_site_verification'] ?? null);
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

    private function extractVerification(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        if (preg_match("/content\\s*=\\s*[\"']([^\"']+)[\"']/i", $raw, $m)) {
            return trim($m[1]);
        }

        return $raw;
    }

    private function cleanId(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        return preg_replace('/[^A-Za-z0-9\\-]/', '', $raw) ?: null;
    }
}
