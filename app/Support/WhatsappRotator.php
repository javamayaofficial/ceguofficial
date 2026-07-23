<?php

namespace App\Support;

class WhatsappRotator
{
    /**
     * Ubah input multi-nomor (baris baru/koma/titik-koma) menjadi daftar bersih.
     *
     * @return list<string>
     */
    public static function numbers(?string $raw): array
    {
        return collect(preg_split('/[\r\n,;]+/', (string) $raw))
            ->map(fn ($number) => preg_replace('/\D/', '', (string) $number))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Pilih nomor secara deterministik dari selector tertentu.
     * Selector yang sama akan selalu menghasilkan nomor yang sama.
     *
     * @param  list<string>  $numbers
     */
    public static function pick(array $numbers, ?string $selector = null): string
    {
        if ($numbers === []) {
            return '';
        }

        $selector = trim((string) $selector);
        if ($selector === '') {
            return $numbers[0];
        }

        $index = abs(crc32($selector)) % count($numbers);

        return $numbers[$index];
    }
}
