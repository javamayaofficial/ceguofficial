<?php

namespace App\Services;

/**
 * Mesin placeholder {{token}} (RFP: Placeholder Dinamis).
 * Toleran spasi: {{layanan}} dan {{ layanan }} sama-sama diganti.
 *
 * Sintaks yang didukung:
 *
 * 1) Penggantian biasa ....... {{layanan}}
 *
 * 2) Blok kondisional ........ {{#harga}} ... {{/harga}}
 *    Isi blok hanya tampil bila token ADA dan TIDAK kosong. Bila tidak ada
 *    (mis. baris CSV tanpa kolom "harga"), SELURUH blok dibuang.
 *    Ini membuat template bisa memakai data lokal riil ({{harga}}, {{garansi}},
 *    {{jadwal}}, dst.) tanpa risiko token mentah bocor ke halaman publik.
 *
 * 3) Blok kebalikan .......... {{^harga}} ... {{/harga}}
 *    Tampil justru ketika token kosong/tidak ada (untuk teks cadangan).
 *
 * 4) Komentar template ....... {{! catatan untuk admin }}
 *    Dibuang saat render; tidak pernah tampil di halaman.
 *
 * Blok kondisional boleh BERSARANG (diproses beberapa lintasan), asalkan tidak
 * memakai token yang sama di dalam dirinya sendiri.
 */
class TokenReplacer
{
    /** Batas lintasan pemrosesan blok bersarang (pengaman anti-loop). */
    private const MAX_PASSES = 6;

    /**
     * @param array<string,string> $tokens
     */
    public static function apply(string $text, array $tokens): string
    {
        // 1) Buang komentar template lebih dulu.
        $text = preg_replace('/\{\{!.*?\}\}/s', '', $text) ?? $text;

        // 2) Proses blok kondisional (beberapa lintasan agar sarang ikut terurai).
        $text = self::conditionals($text, $tokens);

        // 3) Ganti token biasa.
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($m) use ($tokens) {
            $key = $m[1];

            // Token tak dikenal dibiarkan apa adanya agar mudah di-debug di editor.
            return array_key_exists($key, $tokens) ? $tokens[$key] : $m[0];
        }, $text) ?? $text;
    }

    /**
     * @param array<string,string> $tokens
     */
    private static function conditionals(string $text, array $tokens): string
    {
        $has = static function (string $key) use ($tokens): bool {
            if (! array_key_exists($key, $tokens)) {
                return false;
            }

            // Anggap kosong bila hanya spasi atau markup tanpa teks (gambar tetap dihitung isi).
            return trim(strip_tags((string) $tokens[$key], '<img>')) !== '';
        };

        for ($pass = 0; $pass < self::MAX_PASSES; $pass++) {
            $before = $text;

            $text = preg_replace_callback(
                '/\{\{#\s*([a-zA-Z0-9_]+)\s*\}\}(.*?)\{\{\/\s*\1\s*\}\}/s',
                static fn ($m) => $has($m[1]) ? $m[2] : '',
                $text,
            ) ?? $text;

            $text = preg_replace_callback(
                '/\{\{\^\s*([a-zA-Z0-9_]+)\s*\}\}(.*?)\{\{\/\s*\1\s*\}\}/s',
                static fn ($m) => $has($m[1]) ? '' : $m[2],
                $text,
            ) ?? $text;

            if ($text === $before) {
                break; // sudah stabil
            }
        }

        return $text;
    }
}
