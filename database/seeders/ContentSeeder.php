<?php

namespace Database\Seeders;

use App\Models\ContentBlock;
use App\Models\Faq;
use App\Models\Setting;
use App\Models\Template;
use App\Services\ContentRepository;
use Illuminate\Database\Seeder;

/**
 * Mengisi pool variasi konten (PDF 2), FAQ, pengaturan, dan template default aktif.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(database_path('seeders/data/content.json')), true);

        // Content blocks (variasi per section)
        foreach ($data['content_blocks'] as $section => $items) {
            foreach ($items as $content) {
                ContentBlock::firstOrCreate(
                    ['section' => $section, 'content' => $content],
                    ['weight' => 1, 'is_active' => true]
                );
            }
        }

        // FAQ global
        foreach ($data['faqs'] as $i => $faq) {
            Faq::firstOrCreate(
                ['service_id' => null, 'question' => $faq['question']],
                ['answer' => $faq['answer'], 'sort_order' => $i, 'is_active' => true]
            );
        }

        // Settings (WA, brand, schema)
        foreach ($data['settings'] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Setting::updateOrCreate(['key' => 'template_blade_enabled'], ['value' => '0']);

        // ============ SIDIK JARI TEMA (anti-footprint banyak-domain) ============
        // Sekali per instalasi: prefix acak → class CSS, palet, radius, font
        // situs ini berbeda dari instalasi lain meski source code-nya sama.
        Setting::firstOrCreate(
            ['key' => \App\Support\ThemeFingerprint::SETTING_KEY],
            ['value' => \App\Support\ThemeFingerprint::generate()]
        );
        Setting::flushCache();

        // 3 varian susunan template di-seed semuanya (admin bebas berpindah);
        // yang AKTIF dipilih deterministik dari sidik jari instalasi ini —
        // kecuali admin sudah pernah memilih template lain secara manual.
        $variants = [
            'Salespage Varian A' => 'template-default.html',
            'Salespage Varian B' => 'template-variant-b.html',
            'Salespage Varian C' => 'template-variant-c.html',
        ];
        $templates = [];
        foreach ($variants as $name => $file) {
            $templates[] = Template::updateOrCreate(
                ['name' => $name],
                ['content' => file_get_contents(database_path('seeders/data/' . $file)), 'css' => null, 'js' => null]
            );
        }
        if (! Template::where('is_active', true)->exists()) {
            $pick = \App\Support\ThemeFingerprint::templateVariant(count($templates));
            $templates[$pick]->makeActive();
        }

        ContentRepository::flushCache();
        Setting::flushCache();
    }
}
