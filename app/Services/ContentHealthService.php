<?php

namespace App\Services;

use App\Models\ContentBlock;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Setting;

/**
 * Menghitung "kesehatan stok konten" dan status onboarding untuk dashboard.
 *
 * Tujuan: operator awam bisa melihat SATU layar yang menjawab dua pertanyaan —
 * (1) apakah stok kalimat sudah cukup untuk generate massal, dan
 * (2) langkah apa berikutnya. Tanpa perlu memahami SEO teknis.
 */
class ContentHealthService
{
    /**
     * Target minimum variasi UNIK per section sebelum generate massal
     * (selaras dengan docs/PANDUAN-OPERATOR-AWAM.md).
     */
    public const TARGETS = [
        'hero' => 20,
        'intro' => 20,
        'pain_point' => 15,
        'solusi' => 15,
        'usp' => 15,
        'testimoni' => 25,
        'cta' => 10,
        'about' => 5,
        'summary_open' => 8,
        'summary_bridge' => 8,
        'summary_close' => 8,
        'summary_filler' => 4,
    ];

    public const FAQ_TARGET = 10;

    private const LABELS = [
        'hero' => 'Hero (judul besar)',
        'intro' => 'Intro (paragraf pembuka)',
        'pain_point' => 'Pain Point (masalah pelanggan)',
        'solusi' => 'Solusi',
        'usp' => 'USP (keunggulan)',
        'testimoni' => 'Testimoni',
        'cta' => 'CTA (ajakan menghubungi)',
        'about' => 'About (tentang kami)',
        'summary_open' => 'Summary: pembuka',
        'summary_bridge' => 'Summary: jembatan',
        'summary_close' => 'Summary: penutup',
        'summary_filler' => 'Summary: pengisi',
    ];

    /**
     * @return array{
     *   sections: array<int, array{section:string,label:string,count:int,target:int,percent:int,ok:bool}>,
     *   faq: array{count:int,target:int,percent:int,ok:bool},
     *   all_ok: bool,
     *   score: int
     * }
     */
    public function health(): array
    {
        $counts = ContentBlock::query()
            ->where('is_active', true)
            ->selectRaw('section, count(*) as total')
            ->groupBy('section')
            ->pluck('total', 'section');

        $sections = [];
        $percentSum = 0;
        $allOk = true;

        foreach (self::TARGETS as $section => $target) {
            $count = (int) ($counts[$section] ?? 0);
            $percent = (int) min(100, round($count / $target * 100));
            $ok = $count >= $target;
            $allOk = $allOk && $ok;
            $percentSum += $percent;

            $sections[] = [
                'section' => $section,
                'label' => self::LABELS[$section] ?? ucfirst($section),
                'count' => $count,
                'target' => $target,
                'percent' => $percent,
                'ok' => $ok,
            ];
        }

        $faqCount = (int) Faq::where('is_active', true)->count();
        $faqPercent = (int) min(100, round($faqCount / self::FAQ_TARGET * 100));
        $faqOk = $faqCount >= self::FAQ_TARGET;
        $allOk = $allOk && $faqOk;

        return [
            'sections' => $sections,
            'faq' => ['count' => $faqCount, 'target' => self::FAQ_TARGET, 'percent' => $faqPercent, 'ok' => $faqOk],
            'all_ok' => $allOk,
            'score' => (int) round(($percentSum + $faqPercent) / (count(self::TARGETS) + 1)),
        ];
    }

    /**
     * Checklist onboarding — dideteksi otomatis dari kondisi sistem.
     *
     * @return array{steps: array<int, array{title:string,hint:string,done:bool,url:?string}>, done_count:int, total:int, complete:bool}
     */
    public function onboarding(array $health): array
    {
        $hasWa = trim((string) Setting::get('whatsapp_number', '')) !== '';
        $hasPages = Page::query()->exists();
        $hasPublished = Page::published()->exists();

        $steps = [
            [
                'title' => '1. Isi Pengaturan dasar',
                'hint' => 'Nomor WhatsApp, nama brand, dan data organisasi — dipakai di semua halaman.',
                'done' => $hasWa,
                'url' => route('admin.settings.edit'),
            ],
            [
                'title' => '2. Penuhi stok kalimat (Variasi Konten & FAQ)',
                'hint' => 'Capai target minimum agar halaman tidak terlihat kembar di mata Google.',
                'done' => $health['all_ok'],
                'url' => route('admin.content.index'),
            ],
            [
                'title' => '3. Import CSV pertama (mulai 1 kota saja)',
                'hint' => 'Kolom wajib: layanan,kota,kecamatan,kelurahan. Tambahkan kolom data lokal sesuai bisnis Anda (mis. harga, jadwal, landmark) agar tiap halaman makin unik.',
                'done' => $hasPages,
                'url' => route('admin.imports.index'),
            ],
            [
                'title' => '4. Cek sampel lalu Publish bertahap',
                'hint' => 'Buka 5–10 halaman acak dulu. Publish per gelombang, jangan sekaligus.',
                'done' => $hasPublished,
                'url' => route('admin.pages.index'),
            ],
            [
                'title' => '5. Submit sitemap ke Google Search Console',
                'hint' => 'Sekali saja: daftarkan ' . url('/sitemap.xml') . ' lalu pantau menu Indexing → Pages tiap minggu.',
                'done' => $hasPublished, // proxy: dianggap dilakukan setelah publish pertama
                'url' => 'https://search.google.com/search-console',
            ],
        ];

        $doneCount = count(array_filter($steps, fn ($s) => $s['done']));

        return [
            'steps' => $steps,
            'done_count' => $doneCount,
            'total' => count($steps),
            'complete' => $doneCount === count($steps),
        ];
    }
}
