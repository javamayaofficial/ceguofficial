<?php

namespace App\Support;

use App\Models\Setting;
use App\Services\TokenReplacer;

/**
 * Perakit tautan WhatsApp yang aman dari kebocoran token.
 *
 * Setting `whatsapp_message` umumnya berisi template dengan token
 * {{layanan}}, {{kota}}, {{kecamatan}}, {{kelurahan}} — yang HANYA bisa
 * diresolve di salespage. Bila template itu dipakai apa adanya di halaman tanpa
 * konteks lokasi (beranda, kategori, 404), pengunjung menerima pesan WhatsApp
 * berisi tulisan mentah seperti "layanan {{layanan}} di {{kelurahan}}".
 *
 * Helper ini mendeteksi kondisi tersebut dan menggantinya dengan pesan default
 * berbasis brand.
 */
class WaLink
{
    /**
     * Nomor WhatsApp pertama yang valid dari Setting (mendukung daftar
     * dipisah baris/koma/titik-koma).
     */
    public static function number(): string
    {
        return self::numberFor();
    }

    /**
     * Nomor WhatsApp deterministik untuk selector/path tertentu.
     */
    public static function numberFor(?string $selector = null): string
    {
        $numbers = WhatsappRotator::numbers((string) Setting::get('whatsapp_number', ''));

        return WhatsappRotator::pick($numbers, $selector);
    }

    /**
     * Tautan WhatsApp untuk halaman TANPA konteks lokasi (beranda, hub, error).
     * Mengembalikan '#' bila nomor belum diisi.
     */
    public static function generic(?string $brand = null): string
    {
        $brand = $brand ?: (string) Setting::get('brand_name', '');
        $selector = request()?->getPathInfo() ?: '/';
        $num = self::numberFor($selector);
        if ($num === '') {
            return '#';
        }

        return 'https://wa.me/' . $num . '?text=' . rawurlencode(self::message($brand));
    }

    /**
     * Pesan yang aman: pakai template hanya bila TIDAK memuat token konteks.
     */
    public static function message(?string $brand = null): string
    {
        $brand = $brand ?: (string) Setting::get('brand_name', '');
        $raw = (string) Setting::get('whatsapp_message', '');

        $hasContextToken = preg_match('/\{\{\s*(layanan|kota|kecamatan|kelurahan)\s*\}\}/', $raw) === 1;

        if (trim($raw) === '' || $hasContextToken) {
            return "Halo {$brand}, saya ingin konsultasi.";
        }

        return TokenReplacer::apply($raw, ['brand' => $brand]);
    }
}
