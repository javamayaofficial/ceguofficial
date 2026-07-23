<?php

namespace App\Support;

use App\Models\Setting;

/**
 * SIDIK JARI TEMA (anti-footprint untuk model banyak-domain).
 *
 * Masalah: satu source code dipasang di banyak domain → semua situs memakai
 * class CSS `cegu-*`, palet, dan susunan HTML yang identik. Struktur HTML yang
 * kembar di puluhan domain adalah sidik jari jaringan yang mudah dilacak.
 *
 * Solusi: setiap INSTALASI memiliki `theme_prefix` unik (digenerate acak saat
 * seeding, tersimpan di Pengaturan). Dari prefix ini diturunkan secara
 * deterministik:
 *   1. Nama class CSS  → `cegu-hero` menjadi mis. `qx7-hero` (via middleware)
 *   2. Palet warna     → 6 palet kurasi
 *   3. Bentuk (radius) → 4 tingkat kebulatan sudut & tombol
 *   4. Font stack      → 3 kombinasi
 *   5. Varian susunan template → 3 urutan section (dipilih saat seeding)
 *
 * Total kombinasi visual: 6 × 4 × 3 × 3 = 216 — dua domain mana pun hampir
 * pasti berbeda tampang & struktur, tanpa kerja manual dari operator.
 * Admin dapat mengganti `theme_prefix` di Pengaturan untuk "mengocok ulang"
 * penampilan (cache halaman otomatis diperbarui).
 */
class ThemeFingerprint
{
    public const SETTING_KEY = 'theme_prefix';

    /** Prefix aktif instalasi ini (fallback aman: 'cegu'). */
    public static function prefix(): string
    {
        $p = strtolower(trim((string) Setting::get(self::SETTING_KEY, '')));

        return preg_match('/^[a-z][a-z0-9]{1,7}$/', $p) ? $p : 'cegu';
    }

    /** Buat prefix acak baru (huruf awal, 3–4 karakter, bukan 'cegu'). */
    public static function generate(): string
    {
        do {
            $p = chr(random_int(97, 122))
                . chr(random_int(97, 122))
                . random_int(2, 9)
                . (random_int(0, 1) ? chr(random_int(97, 122)) : '');
        } while ($p === 'cegu');

        return $p;
    }

    /**
     * Terapkan prefix ke HTML final (class CSS di markup DAN di CSS inline
     * ikut berganti karena keduanya berada dalam satu output HTML).
     */
    public static function apply(string $html): string
    {
        $prefix = self::prefix();
        if ($prefix === 'cegu') {
            return $html;
        }

        return str_replace('cegu-', $prefix . '-', $html);
    }

    /**
     * Override CSS variables yang diturunkan deterministik dari prefix —
     * palet, radius, dan font berbeda antar instalasi tanpa disentuh admin.
     */
    public static function cssOverrides(): string
    {
        $seed = crc32('daya:' . self::prefix());

        $palettes = [
            // [utama, utama-gelap, aksen, tombol, latar, garis]
            ['#045323', '#013616', '#f2c300', '#a80000', '#f6f7f4', '#e6e9e4'], // hijau-emas (asli)
            ['#123c69', '#0a2647', '#e8b13d', '#bb4430', '#f4f6f9', '#e2e7ee'], // navy-amber
            ['#5c2018', '#3d1511', '#d9a45b', '#1f6650', '#faf6f1', '#ece3d7'], // marun-pasir
            ['#0f5257', '#083a3e', '#f0a03a', '#c94f3d', '#f2f7f6', '#dde9e7'], // teal-oranye
            ['#3b3a5a', '#26253d', '#c9a227', '#8c2f39', '#f6f5f9', '#e5e4ee'], // ungu tua-emas
            ['#4a3728', '#33261b', '#c98a2c', '#356648', '#f9f6f2', '#ebe3d9'], // cokelat-hijau
        ];
        [$g, $gd, $gold, $red, $bg, $line] = $palettes[$seed % count($palettes)];

        $radii = ['6px', '10px', '16px', '24px'];
        $radius = $radii[intdiv($seed, 7) % count($radii)];

        $btnRadii = ['8px', '14px', '30px'];
        $btnRadius = $btnRadii[intdiv($seed, 13) % count($btnRadii)];

        $fonts = [
            '"Poppins",-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif',
            '"Segoe UI",system-ui,-apple-system,Roboto,"Helvetica Neue",Arial,sans-serif',
            '"Trebuchet MS","Segoe UI",Verdana,Arial,sans-serif',
        ];
        $font = $fonts[intdiv($seed, 31) % count($fonts)];

        return ":root{--g:{$g};--gd:{$gd};--gold:{$gold};--red:{$red};--bg:{$bg};--line:{$line};--radius:{$radius}}"
            . "body.cegu-page{font-family:{$font}}"
            . ".cegu-btn{border-radius:{$btnRadius}}";
    }

    /** Indeks varian susunan template (0..n-1) untuk instalasi ini. */
    public static function templateVariant(int $variantCount): int
    {
        return crc32('tpl:' . self::prefix()) % max(1, $variantCount);
    }
}
