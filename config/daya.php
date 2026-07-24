<?php

/*
|--------------------------------------------------------------------------
| DAYA AI — Konfigurasi Mesin pSEO
|--------------------------------------------------------------------------
| Mesin ini dipakai untuk banyak bisnis (satu instalasi = satu bisnis/klien).
| "engine_name" adalah nama PRODUK yang tampil di panel admin, sedangkan
| nama brand klien diatur terpisah lewat Pengaturan → Nama Brand.
|
| CATATAN KOMPATIBILITAS: variabel .env lama (CEGU_*) masih dibaca sebagai
| cadangan, sehingga server yang belum diperbarui tetap berjalan normal.
*/

return [

    // Nama mesin yang tampil di panel admin.
    'engine_name' => env('DAYA_ENGINE_NAME', 'CEGU'),

    // Cache HTML halaman salespage (detik). 0 = nonaktif.
    'page_cache_ttl' => (int) env('DAYA_PAGE_CACHE_TTL', env('CEGU_PAGE_CACHE_TTL', 900)),

    // Cache daftar halaman kategori/hub (detik).
    'category_cache_ttl' => (int) env('DAYA_CATEGORY_CACHE_TTL', env('CEGU_CATEGORY_CACHE_TTL', 600)),

    /*
    |--------------------------------------------------------------------------
    | Blade di template (KEAMANAN)
    |--------------------------------------------------------------------------
    | Bila true, template salespage boleh memuat sintaks Blade (@if, @foreach)
    | yang berarti EKSEKUSI PHP. Kuat namun berbahaya: siapa pun yang bisa
    | mengedit template praktis bisa menjalankan kode di server.
    |
    | Defense-in-depth: fitur menyala HANYA jika keduanya benar — saklar ini
    | (dari .env, di luar jangkauan panel/DB) DAN toggle 'template_blade_enabled'
    | di Pengaturan. Default: MATI.
    */
    'template_blade' => (bool) env('DAYA_TEMPLATE_BLADE', env('CEGU_TEMPLATE_BLADE', false)),

    /*
    |--------------------------------------------------------------------------
    | Anti halaman tipis (keunikan)
    |--------------------------------------------------------------------------
    | thin_min_local_facts : jumlah minimal fakta lokal riil agar halaman
    |   dianggap "berisi". Di bawah ini → halaman dianggap tipis.
    | thin_canonical_to_hub: bila true, halaman tipis meng-canonical ke hub
    |   kecamatan (memusatkan sinyal, menghindari duplikat).
    */
    'thin_min_local_facts' => (int) env('DAYA_THIN_MIN_FACTS', env('CEGU_THIN_MIN_FACTS', 2)),
    'thin_canonical_to_hub' => (bool) env('DAYA_THIN_CANONICAL_HUB', env('CEGU_THIN_CANONICAL_HUB', true)),

    /*
    |--------------------------------------------------------------------------
    | Rate limit halaman publik (per menit per IP)
    |--------------------------------------------------------------------------
    | Melindungi server saat digempur crawler/bot. Cukup longgar untuk manusia.
    */
    'public_rate_limit' => (int) env('DAYA_PUBLIC_RATE_LIMIT', env('CEGU_PUBLIC_RATE_LIMIT', 120)),

    /*
    |--------------------------------------------------------------------------
    | FAQ per halaman (keunikan konten)
    |--------------------------------------------------------------------------
    */
    'faq_per_page' => (int) env('DAYA_FAQ_PER_PAGE', 8),
    'faq_always' => (int) env('DAYA_FAQ_ALWAYS', 3),

];
