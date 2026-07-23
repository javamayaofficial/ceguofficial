<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Palet warna per instalasi.
 *
 * Mesin ini dipakai untuk banyak produk/bisnis. Strukturnya sengaja seragam —
 * yang berbeda cukup WARNA (dan logo/gambar yang diunggah lewat Pengaturan).
 * Kelas ini membaca warna dari Pengaturan lalu menimpa variabel CSS dasar.
 *
 * Keamanan: nilai yang tidak berbentuk kode HEX diabaikan, sehingga admin tidak
 * bisa menyuntikkan CSS/skrip sembarangan lewat kolom warna.
 */
class BrandColors
{
    /**
     * Peta: kunci Setting => variabel CSS + nilai bawaan.
     *
     * @var array<string,array{0:string,1:string,2:string}> [css_var, default, label]
     */
    public const MAP = [
        'color_primary' => ['--g', '#2b8a99', 'Warna utama (hero, tombol, judul)'],
        'color_primary_dark' => ['--gd', '#1f6a76', 'Warna utama gelap (gradient & hover)'],
        'color_accent' => ['--gold', '#f5a623', 'Warna aksen (angka, sorotan)'],
        'color_cta' => ['--red', '#e8543f', 'Warna tombol sekunder'],
        'color_bg' => ['--bg', '#f2fbfc', 'Latar halaman'],
        'color_ink' => ['--ink', '#173d44', 'Warna teks utama'],
    ];

    /**
     * Blok CSS penimpa. String kosong bila admin belum mengatur apa pun,
     * sehingga palet bawaan tetap berlaku.
     */
    public static function cssOverrides(): string
    {
        $baris = [];

        foreach (self::MAP as $key => [$cssVar, $default, $label]) {
            $nilai = self::hex((string) Setting::get($key, ''));
            if ($nilai !== null && strcasecmp($nilai, $default) !== 0) {
                $baris[] = "{$cssVar}:{$nilai}";
            }
        }

        if ($baris === []) {
            return '';
        }

        // --teal-bright ikut warna utama bila tidak diatur khusus, agar aksen
        // logo/ikon tetap selaras.
        $primary = self::hex((string) Setting::get('color_primary', ''));
        if ($primary !== null) {
            $baris[] = '--teal-bright:' . $primary;
        }

        return ':root{' . implode(';', $baris) . '}';
    }

    /**
     * Validasi kode warna HEX (#abc atau #aabbcc). Mengembalikan null bila tidak valid.
     */
    public static function hex(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (! str_starts_with($value, '#')) {
            $value = '#' . $value;
        }

        return preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) === 1 ? $value : null;
    }

    /**
     * Nilai untuk ditampilkan di form (nilai tersimpan, atau bawaan).
     */
    public static function current(string $key): string
    {
        $tersimpan = self::hex((string) Setting::get($key, ''));

        return $tersimpan ?? (self::MAP[$key][1] ?? '#000000');
    }
}
