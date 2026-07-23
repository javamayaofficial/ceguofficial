<?php

namespace App\Support;

/**
 * Paket halaman statis siap pakai per JENIS USAHA.
 *
 * Mesin ini dipakai lintas produk, dan tiap produk butuh struktur menu berbeda:
 * toko butuh "Cara Order" & "Pengiriman", properti butuh "Simulasi KPR",
 * klinik butuh "Jadwal Praktik". Karena itu halaman statis TIDAK dibuat saat
 * instalasi — operator memilih paket yang sesuai setelah domain terpasang.
 *
 * Isi halaman hanyalah KERANGKA AWAL berisi token ({{brand}}, {{situs_tentang}})
 * yang wajib disunting operator dengan informasi bisnis yang sebenarnya.
 */
class PagePresets
{
    /**
     * @return array<string,array{label:string,desc:string,pages:array<int,array<string,mixed>>}>
     */
    public static function all(): array
    {
        return [
            'jasa' => [
                'label' => 'Jasa Umum',
                'desc' => 'Servis, perbaikan, konsultan, kontraktor',
                'pages' => [
                    self::tentang(),
                    self::page('layanan', 'Layanan Kami', 'Layanan',
                        'Daftar layanan yang kami tawarkan.',
                        "<h2>Layanan yang Kami Sediakan</h2>\n<p>{{brand}} menyediakan berbagai layanan untuk kebutuhan Anda. Silakan sunting bagian ini dengan daftar layanan yang sebenarnya.</p>\n{{situs_keunggulan}}\n<ul>\n<li>Layanan pertama — keterangan singkat</li>\n<li>Layanan kedua — keterangan singkat</li>\n<li>Layanan ketiga — keterangan singkat</li>\n</ul>"),
                    self::page('cara-pesan', 'Cara Pesan', 'Cara Pesan',
                        'Langkah memesan layanan kami.',
                        "<h2>Tiga Langkah Mudah</h2>\n<p>Sunting langkah di bawah sesuai alur kerja Anda.</p>\n{{situs_proses}}\n<ol>\n<li><strong>Hubungi kami</strong> — sampaikan kebutuhan Anda lewat WhatsApp.</li>\n<li><strong>Konsultasi</strong> — kami bantu tentukan pilihan dan rincian biaya.</li>\n<li><strong>Pengerjaan</strong> — layanan berjalan sesuai jadwal yang disepakati.</li>\n</ol>"),
                    self::kontak(),
                ],
            ],

            'produk' => [
                'label' => 'Produk / Toko',
                'desc' => 'Herbal, kosmetik, makanan, barang fisik',
                'pages' => [
                    self::tentang(),
                    self::page('produk', 'Produk Kami', 'Produk',
                        'Katalog produk yang kami sediakan.',
                        "<h2>Katalog Produk</h2>\n<p>Sunting bagian ini dengan daftar produk Anda beserta keterangan singkat.</p>\n{{situs_galeri}}"),
                    self::page('cara-order', 'Cara Order', 'Cara Order',
                        'Langkah memesan produk kami.',
                        "<h2>Cara Memesan</h2>\n{{situs_proses}}\n<ol>\n<li>Hubungi kami lewat WhatsApp dan sebutkan produk yang diinginkan.</li>\n<li>Kami konfirmasi ketersediaan, total biaya, dan ongkos kirim.</li>\n<li>Lakukan pembayaran, lalu pesanan kami proses.</li>\n</ol>"),
                    self::page('pengiriman', 'Pengiriman', 'Pengiriman',
                        'Informasi pengiriman dan estimasi waktu.',
                        "<h2>Pengiriman</h2>\n<p><strong>Sunting bagian ini dengan kebijakan pengiriman Anda yang sebenarnya</strong> — jasa ekspedisi, estimasi waktu, dan wilayah jangkauan.</p>\n<ul>\n<li>Ekspedisi yang tersedia: —</li>\n<li>Estimasi waktu: —</li>\n<li>Wilayah jangkauan: —</li>\n</ul>"),
                    self::kontak(),
                ],
            ],

            'properti' => [
                'label' => 'Properti',
                'desc' => 'Agen, developer, sewa, kos',
                'pages' => [
                    self::tentang(),
                    self::page('listing', 'Daftar Properti', 'Properti',
                        'Properti yang sedang kami tawarkan.',
                        "<h2>Properti Tersedia</h2>\n<p>Sunting dengan daftar properti Anda. Sertakan lokasi, luas, dan kisaran harga.</p>\n{{situs_galeri}}"),
                    self::page('cara-beli', 'Proses Pembelian', 'Proses',
                        'Tahapan membeli properti bersama kami.',
                        "<h2>Tahapan Pembelian</h2>\n{{situs_proses}}\n<ol>\n<li>Konsultasi kebutuhan dan anggaran.</li>\n<li>Survei lokasi bersama tim kami.</li>\n<li>Pengurusan berkas dan akad.</li>\n</ol>\n<p class=\"muted\">Sunting sesuai prosedur yang Anda jalankan.</p>"),
                    self::kontak(),
                ],
            ],

            'pendidikan' => [
                'label' => 'Pendidikan / Kursus',
                'desc' => 'Bimbel, les privat, pelatihan',
                'pages' => [
                    self::tentang(),
                    self::page('program', 'Program Belajar', 'Program',
                        'Program yang kami sediakan.',
                        "<h2>Program Kami</h2>\n<p>Sunting dengan daftar program, jenjang, dan durasi yang sebenarnya.</p>\n{{situs_keunggulan}}\n<ul>\n<li>Program pertama — jenjang, durasi</li>\n<li>Program kedua — jenjang, durasi</li>\n</ul>"),
                    self::page('cara-daftar', 'Cara Daftar', 'Cara Daftar',
                        'Langkah pendaftaran.',
                        "<h2>Pendaftaran</h2>\n{{situs_proses}}\n<ol>\n<li>Hubungi kami dan sampaikan kebutuhan belajar.</li>\n<li>Konsultasi gratis untuk menentukan program yang sesuai.</li>\n<li>Jadwal disepakati, pembelajaran dimulai.</li>\n</ol>"),
                    self::kontak(),
                ],
            ],

            'kesehatan' => [
                'label' => 'Kesehatan / Klinik',
                'desc' => 'Klinik, terapi, perawatan',
                'pages' => [
                    self::tentang(),
                    self::page('layanan', 'Layanan Kesehatan', 'Layanan',
                        'Layanan yang tersedia di tempat kami.',
                        "<h2>Layanan Kami</h2>\n<p>Sunting dengan daftar layanan Anda.</p>\n{{situs_keunggulan}}\n<p><strong>Catatan penting:</strong> hindari menjanjikan kesembuhan atau hasil pasti. Sampaikan layanan apa adanya.</p>"),
                    self::page('jadwal-praktik', 'Jadwal Praktik', 'Jadwal',
                        'Jam operasional dan jadwal praktik.',
                        "<h2>Jadwal Praktik</h2>\n<p>Sunting dengan jadwal yang sebenarnya.</p>\n<ul>\n<li>Senin–Jumat: —</li>\n<li>Sabtu: —</li>\n<li>Minggu &amp; hari libur: —</li>\n</ul>"),
                    self::kontak(),
                ],
            ],

            'minimal' => [
                'label' => 'Minimal',
                'desc' => 'Hanya Tentang & Kontak — untuk situs sederhana',
                'pages' => [self::tentang(), self::kontak()],
            ],
        ];
    }

    // ---------------------------------------------------------------
    // Halaman yang dipakai berulang
    // ---------------------------------------------------------------

    /**
     * @return array<string,mixed>
     */
    private static function tentang(): array
    {
        return self::page('tentang-kami', 'Tentang Kami', 'Tentang',
            'Mengenal lebih dekat siapa kami dan apa yang kami kerjakan.',
            "<h2>Siapa Kami</h2>\n<p><strong>Sunting bagian ini dengan cerita bisnis Anda yang sebenarnya</strong> — kapan mulai, apa yang dikerjakan, dan siapa yang dilayani.</p>\n{{situs_tentang}}\n<h2>Nilai yang Kami Pegang</h2>\n<ul>\n<li>Transparan dalam harga dan proses</li>\n<li>Responsif saat dihubungi</li>\n<li>Mengutamakan kebutuhan pelanggan</li>\n</ul>");
    }

    /**
     * @return array<string,mixed>
     */
    private static function kontak(): array
    {
        return self::page('kontak-kami', 'Kontak Kami', 'Kontak',
            'Hubungi kami untuk pertanyaan dan pemesanan.',
            "<h2>Hubungi Kami</h2>\n<p>Cara tercepat menghubungi {{brand}} adalah lewat WhatsApp — tombolnya ada di setiap halaman.</p>\n<h3>Informasi Kontak</h3>\n<ul>\n<li><strong>Alamat:</strong> — <em>(isi alamat Anda)</em></li>\n<li><strong>Jam operasional:</strong> — <em>(isi jam buka)</em></li>\n<li><strong>Email:</strong> — <em>(opsional)</em></li>\n</ul>\n<p class=\"muted\">Sunting bagian di atas dengan data yang benar sebelum situs dipublikasikan.</p>");
    }

    /**
     * @return array<string,mixed>
     */
    private static function page(string $slug, string $title, string $menu, string $desc, string $content): array
    {
        return [
            'slug' => $slug,
            'title' => $title,
            'menu_label' => $menu,
            'meta_description' => $desc,
            'content' => $content,
            'show_in_nav' => true,
            'show_in_footer' => true,
            'is_active' => true,
        ];
    }
}
