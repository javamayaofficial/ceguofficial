<?php

namespace App\Services;

use App\Models\Template;

class TemplateValidator
{
    public const TOKEN_SALESPAGE = [
        'hero', 'intro', 'cta', 'about', 'summary',
        'usp_list', 'pain_point_list', 'solusi_list', 'testimoni_list',
        'breadcrumb', 'internal_links', 'faq',
        'layanan', 'kota', 'kecamatan', 'kelurahan', 'brand', 'year',
        'wa', 'wa_number', 'wa_button',
        'hero_image', 'hero_alt', 'gambar_keunggulan', 'gambar_solusi',
        'gambar_proses', 'gambar_tentang', 'galeri',
        'katalog_layanan', 'kredensial', 'fakta_lokal', 'referensi',
        'tips_list', 'perbandingan_list', 'cakupan_list',
        'harga', 'jadwal', 'jam_operasional', 'landmark', 'garansi', 'legalitas',
        'pengalaman', 'jumlah_tutor', 'sekolah', 'stok', 'pengiriman',
        'izin_bpom', 'komposisi', 'kamar', 'luas_tanah', 'luas_bangunan', 'isi',
    ];

    public const TOKEN_HOME_EXTRA = ['daftar_kota', 'daftar_layanan'];

    private const WAJIB = [
        'hero' => 'judul utama halaman',
        'wa' => 'tombol WhatsApp (tanpa ini tidak ada lead)',
    ];

    private const DISARANKAN_SALESPAGE = [
        'breadcrumb' => 'jalur navigasi ke hub wilayah',
        'internal_links' => 'tautan ke wilayah lain (internal linking)',
        'faq' => 'daftar FAQ — bahan utama kutipan AI Overview',
        'usp_list' => 'variasi keunggulan (tanpa ini pool keunggulan tidak terpakai)',
        'pain_point_list' => 'variasi kendala',
        'solusi_list' => 'variasi solusi',
        'testimoni_list' => 'variasi testimoni',
        'kelurahan' => 'nama wilayah — pembeda utama antar halaman',
    ];

    private const DISARANKAN_HOME = [
        'faq' => 'daftar FAQ',
        'katalog_layanan' => 'daftar layanan + harga',
    ];

    private const ALTERNATIF_HOME = [
        'tautan_internal' => [
            'token' => ['internal_links', 'daftar_kota', 'daftar_layanan'],
            'guna' => 'tautan ke layanan/wilayah (internal linking) — pakai {{internal_links}} atau {{daftar_kota}}/{{daftar_layanan}}',
        ],
    ];

    public static function periksa(string $konten, string $type = Template::TYPE_SALESPAGE): array
    {
        $hasil = [];
        $isHome = $type === Template::TYPE_HOME;

        $dikenal = array_merge(self::TOKEN_SALESPAGE, $isHome ? self::TOKEN_HOME_EXTRA : []);

        preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $konten, $m);
        $dipakai = array_unique($m[1] ?? []);

        $asing = array_values(array_diff($dipakai, $dikenal));
        if ($asing !== []) {
            $hasil[] = [
                'level' => 'error',
                'pesan' => 'Token tidak dikenali: {{' . implode('}}, {{', $asing) . '}}. Token ini akan dihapus saat render.',
            ];
        }

        foreach (self::WAJIB as $token => $guna) {
            if (! in_array($token, $dipakai, true)) {
                $hasil[] = ['level' => 'error', 'pesan' => "Token {{{$token}}} tidak ada — {$guna}."];
            }
        }

        foreach ($isHome ? self::DISARANKAN_HOME : self::DISARANKAN_SALESPAGE as $token => $guna) {
            if (! in_array($token, $dipakai, true)) {
                $hasil[] = ['level' => 'warn', 'pesan' => "Sebaiknya ada {{{$token}}} — {$guna}."];
            }
        }

        if ($isHome) {
            foreach (self::ALTERNATIF_HOME as $alt) {
                if (array_intersect($alt['token'], $dipakai) === []) {
                    $hasil[] = ['level' => 'warn', 'pesan' => 'Belum ada ' . $alt['guna'] . '.'];
                }
            }
        }

        $h1 = preg_match_all('/<h1[\s>]/i', $konten);
        if ($h1 === 0) {
            $hasil[] = ['level' => 'error', 'pesan' => 'Tidak ada <h1>. Setiap halaman butuh tepat satu judul utama.'];
        } elseif ($h1 > 1) {
            $hasil[] = ['level' => 'warn', 'pesan' => "Ada {$h1} tag <h1>. Sebaiknya hanya satu per halaman."];
        }

        foreach (['div', 'section', 'header', 'p', 'a'] as $tag) {
            $buka = preg_match_all('/<' . $tag . '[\s>]/i', $konten);
            $tutup = preg_match_all('#</' . $tag . '>#i', $konten);
            if ($buka !== $tutup) {
                $hasil[] = [
                    'level' => 'error',
                    'pesan' => "Tag <{$tag}> tidak seimbang: {$buka} dibuka, {$tutup} ditutup.",
                ];
            }
        }

        preg_match_all('/class="([^"]*)"/i', $konten, $cm);
        $semuaKelas = [];
        foreach ($cm[1] ?? [] as $kelas) {
            foreach (preg_split('/\s+/', trim($kelas)) ?: [] as $item) {
                if ($item !== '') {
                    $semuaKelas[] = $item;
                }
            }
        }
        $asingKelas = array_values(array_unique(array_filter(
            $semuaKelas,
            fn ($kelas) => ! str_starts_with($kelas, 'cegu-') && ! in_array($kelas, ['in', 'lead', 'ico', 'n', 'l', 'muted', 'right', 'row'], true),
        )));
        if ($asingKelas !== []) {
            $hasil[] = [
                'level' => 'info',
                'pesan' => 'Kelas CSS di luar sistem: ' . implode(', ', array_slice($asingKelas, 0, 8)) . '.',
            ];
        }

        if (preg_match('/\{\{[#^\/!]/', $konten)) {
            $hasil[] = [
                'level' => 'warn',
                'pesan' => 'Template memakai sintaks kondisional. Pastikan server memakai TokenReplacer versi yang mendukungnya.',
            ];
        }

        if ($isHome) {
            $lokasi = array_intersect(['kelurahan', 'kecamatan', 'kota'], $dipakai);
            if ($lokasi !== []) {
                $hasil[] = [
                    'level' => 'info',
                    'pesan' => 'Token lokasi di beranda akan diisi label wilayah layanan, bukan nama kelurahan tertentu.',
                ];
            }
        }

        if (mb_strlen(strip_tags($konten)) < 200) {
            $hasil[] = ['level' => 'warn', 'pesan' => 'Template terasa sangat pendek.'];
        }

        return $hasil;
    }
}
