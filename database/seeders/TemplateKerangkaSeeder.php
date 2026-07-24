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
        '01-navigasi' => 'Beranda: Navigasi — pusat penjelajahan (disarankan)',
        '02-penjualan' => 'Beranda: Penjualan — beranda sebagai penawaran',
        '03-ringkas' => 'Beranda: Ringkas — situs sederhana',
        '04-korporat' => 'Beranda: Korporat — profil perusahaan',
        '05-katalog' => 'Beranda: Katalog — produk/layanan di depan',
        '06-cerita' => 'Beranda: Cerita — tentang kami lebih dulu',
        '07-lokal' => 'Beranda: Lokal — wilayah layanan ditonjolkan',
        '08-konversi' => 'Beranda: Konversi — fokus tombol WhatsApp',
        '09-otoritas' => 'Beranda: Otoritas — kredensial di depan (hukum/kesehatan)',
        '10-portofolio' => 'Beranda: Portofolio — galeri hasil kerja',
        '11-edukasi' => 'Beranda: Edukasi — menjawab pertanyaan lebih dulu',
        '12-daftar' => 'Beranda: Daftar — direktori layanan & wilayah',
    ];

    public function run(): void
    {
        $this->muat(resource_path('templates'), Template::TYPE_SALESPAGE);
        $this->muat(resource_path('templates/home'), Template::TYPE_HOME);
    }

    private function muat(string $dir, string $type): void
    {
        if (! is_dir($dir)) {
            $this->command?->warn('Folder resources/templates tidak ditemukan — tidak ada yang dimuat.');

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
                'type' => $type,
                'content' => $isi,
                'is_active' => false,
            ]);
            $dibuat++;
        }

        $label = $type === Template::TYPE_HOME ? 'beranda' : 'salespage';
        $this->command?->info("Template {$label}: {$dibuat} dimuat, {$dilewati} dilewati (sudah ada).");
    }
}
