<?php

namespace App\Services;

use App\Models\Page;

/**
 * Deteksi "halaman tipis" untuk pSEO skala besar.
 *
 * Ribuan halaman {layanan}×lokasi yang nyaris kembar adalah profil risiko utama
 * (scaled content abuse). Pembeda paling jujur adalah DATA LOKAL RIIL — kolom
 * CSV opsional `extra` (harga, jumlah_tutor, landmark, dll.). Service ini
 * menghitung seberapa "berisi" sebuah halaman dan menandai yang terlalu miskin
 * agar bisa diperlakukan berbeda (mis. canonical ke hub kecamatan).
 *
 * Catatan: kunci geografis (lat/lng) TIDAK dihitung sebagai fakta konten —
 * itu metadata, bukan nilai bagi pembaca.
 */
class UniquenessService
{
    /** Kunci `extra` yang bukan "fakta konten" (tidak menambah keunikan bagi pembaca). */
    private const NON_CONTENT_KEYS = ['lat', 'lng', 'latitude', 'longitude', 'geo', 'koordinat'];

    /**
     * Jumlah fakta lokal riil (kolom extra terisi & bermakna).
     */
    public function localFactCount(Page $page): int
    {
        $extra = $page->extra ?? [];
        if (! is_array($extra)) {
            return 0;
        }

        $count = 0;
        foreach ($extra as $key => $value) {
            if (in_array(strtolower((string) $key), self::NON_CONTENT_KEYS, true)) {
                continue;
            }
            if (is_scalar($value) && trim((string) $value) !== '') {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Apakah halaman ini "tipis" (di bawah ambang fakta lokal minimal)?
     */
    public function isThin(Page $page): bool
    {
        $min = (int) config('daya.thin_min_local_facts', 2);

        return $this->localFactCount($page) < $min;
    }

    /**
     * Skor keunikan kasar 0–100 (untuk indikator/laporan). Bukan angka ajaib,
     * hanya proksi: makin banyak fakta lokal, makin tinggi.
     */
    public function score(Page $page): int
    {
        $facts = $this->localFactCount($page);
        // 5 fakta lokal dianggap sudah "kaya" → 100.
        return (int) min(100, round($facts / 5 * 100));
    }
}
