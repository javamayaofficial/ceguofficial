<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateKerangkaSeeder extends Seeder
{
    private const LABEL = [
        '01-aida' => 'AIDA — Attention, Interest, Desire, Action',
        '02-pas' => 'PAS — Problem, Agitate, Solution',
        '03-bab' => 'BAB — Before, After, Bridge',
        '04-4p' => '4P — Promise, Picture, Proof, Push',
        '05-quest' => 'QUEST — Qualify, Understand, Educate, Stimulate, Transition',
        '06-tofu' => 'TOFU — Awareness (audiens baru)',
        '07-mofu' => 'MOFU — Pertimbangan',
        '08-bofu' => 'BOFU — Siap membeli / closing',
        '09-vsl' => 'VSL — Video Sales Letter',
        '10-advertorial' => 'Advertorial — Soft selling ala artikel',
        '11-longform' => 'Long Form — Penjualan mendalam',
        '12-fsp' => 'FSP — Fakta, Story, Penawaran',
    ];

    public function run(): void
    {
        $dir = resource_path('templates');

        if (! is_dir($dir)) {
            $this->command?->warn('Folder resources/templates tidak ditemukan.');

            return;
        }

        $berkas = glob($dir . '/*.html') ?: [];
        if ($berkas === []) {
            $this->command?->warn('Tidak ada berkas .html di resources/templates.');

            return;
        }

        $dibuat = 0;
        $dilewati = 0;

        foreach ($berkas as $path) {
            $slug = pathinfo($path, PATHINFO_FILENAME);
            $nama = self::LABEL[$slug] ?? ucwords(str_replace('-', ' ', $slug));

            if (Template::where('name', $nama)->exists()) {
                $dilewati++;
                continue;
            }

            $isi = (string) file_get_contents($path);
            if (trim($isi) === '') {
                continue;
            }

            Template::create([
                'name' => $nama,
                'content' => $isi,
                'is_active' => false,
            ]);
            $dibuat++;
        }

        $this->command?->info("Template kerangka: {$dibuat} dimuat, {$dilewati} dilewati.");
    }
}
