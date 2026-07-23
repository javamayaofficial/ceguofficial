<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Setting;
use App\Models\Template;
use Illuminate\Support\Facades\Blade;

/**
 * Orkestrator render salespage.
 *
 * Mengubah satu baris `pages` (data ringkas) menjadi HTML lengkap dengan
 * memadukan: template aktif + token dinamis + variasi konten deterministik +
 * summary + FAQ + internal link + SEO/schema. Inilah implementasi prinsip RFP
 * "1 TEMPLATE = JUTAAN HALAMAN".
 */
class PageRenderer
{
    public function __construct(
        private readonly ContentRepository $content,
        private readonly SummaryGenerator $summaryGenerator,
        private readonly InternalLinkService $internalLinks,
        private readonly SeoService $seo,
    ) {
    }

    /**
     * @return array{page:Page, template:?Template, body:string, css:?string, js:?string, seo:array, wa:string}
     */
    public function render(Page $page): array
    {
        $page->loadMissing(['service', 'city', 'district', 'village']);
        $template = $page->template ?? Template::active();
        $engine = VariationEngine::forSeed($page->variation_seed);

        $base = $this->baseTokens($page);

        // --- Pilih variasi konten (deterministik) lalu resolve token di dalamnya ---
        $blocks = $this->content->blocksBySection();

        $hero = $this->resolveBlock($engine->pick('hero', $blocks['hero'] ?? []) ?? '', $base);
        $intro = $this->resolveBlock($engine->pick('intro', $blocks['intro'] ?? []) ?? '', $base);
        $about = $this->resolveBlock($engine->pick('about', $blocks['about'] ?? []) ?? '', $base);
        $cta = $this->resolveBlock($engine->pick('cta', $blocks['cta'] ?? []) ?? '', $base);

        $painPoints = $this->resolveMany($engine->pickMany('pain_point', $blocks['pain_point'] ?? [], 3), $base);
        $solusi = $this->resolveMany($engine->pickMany('solusi', $blocks['solusi'] ?? [], 3), $base);
        $usp = $this->resolveMany($engine->pickMany('usp', $blocks['usp'] ?? [], 4), $base);
        $testimoni = $this->resolveMany($engine->pickMany('testimoni', $blocks['testimoni'] ?? [], 3), $base);

        $summary = $this->summaryGenerator->generate($base, $usp, $engine, $page->extra ?? []);

        // --- FAQ (global + per layanan), resolve token di pertanyaan & jawaban ---
        $faqs = array_map(fn ($f) => [
            'question' => TokenReplacer::apply($f['question'], $base),
            'answer' => TokenReplacer::apply($f['answer'], $base),
        ], $this->content->faqsForService($page->service_id));

        $links = $this->internalLinks->relatedFor($page);
        $seo = $this->seo->build($page, $base, $hero, $summary, $faqs);

        // Settings untuk token gambar section (dipakai di array tokens di bawah).
        $siteSettings = Setting::map();

        // --- Token siap pakai untuk template ---
        $tokens = array_merge($base, [
            'hero' => e($hero),
            'intro' => e($intro),
            'about' => e($about),
            'cta' => e($cta),
            'summary' => e($summary),
            'pain_point' => e($painPoints[0] ?? ''),
            'solusi' => e($solusi[0] ?? ''),
            'usp' => e($usp[0] ?? ''),
            'testimoni' => e($testimoni[0] ?? ''),
            'pain_point_list' => $this->htmlList($painPoints, 'cegu-painpoints'),
            'solusi_list' => $this->htmlList($solusi, 'cegu-solusi'),
            'usp_list' => $this->htmlList($usp, 'cegu-usp'),
            'testimoni_list' => $this->htmlTestimonials($testimoni),
            'faq' => $this->htmlFaq($faqs),
            'breadcrumb' => $this->htmlBreadcrumb($seo['breadcrumb']),
            'internal_links' => $this->htmlInternalLinks($links),
            'wa_button' => $this->htmlWaButton($base['wa'], $cta ?: 'Hubungi via WhatsApp'),
            // Blok "Fakta Lokal" otomatis dari data CSV opsional (kosong bila tak ada).
            'fakta_lokal' => $this->htmlFaktaLokal($page->extra ?? []),
            // Gambar section (opsional — kosong bila URL tidak diisi di Pengaturan).
            // Alt otomatis memuat layanan + lokasi untuk SEO (diambil dari relasi).
            'gambar_solusi' => $this->htmlSectionImage(
                $siteSettings['image_solusi'] ?? '',
                'Solusi ' . ($page->service->name ?? '') . ' di ' . ($page->village->name ?? '')
            ),
            'gambar_keunggulan' => $this->htmlSectionImage(
                $siteSettings['image_keunggulan'] ?? '',
                'Keunggulan ' . ($page->service->name ?? '') . ' di ' . ($page->village->name ?? '') . ', ' . ($page->city->name ?? '')
            ),
            // Slot gambar tambahan. Alt disusun dari data halaman sehingga
            // gambar yang SAMA tetap menghasilkan alt berbeda tiap halaman.
            'gambar_proses' => $this->htmlSectionImage(
                $siteSettings['image_proses'] ?? '',
                'Proses ' . ($page->service->name ?? '') . ' di ' . ($page->village->name ?? '') . ', ' . ($page->district->name ?? '')
            ),
            'galeri' => $this->htmlGaleri($siteSettings, $page),
            'gambar_tentang' => $this->htmlSectionImage(
                $siteSettings['image_tentang'] ?? '',
                trim('Tentang ' . ($page->service->name ?? '') . ' di ' . ($page->district->name ?? '')
                    . ' - ' . ($siteSettings['brand_name'] ?? ''), ' -')
            ),
            // Gambar hero (URL diatur di Pengaturan). Alt = judul hero → SEO friendly.
            'hero_image' => $this->htmlHeroImage(Setting::get('hero_image'), $hero),
            'hero_image_url' => e((string) Setting::get('hero_image', '')),
            'hero_alt' => e($hero),
            'year' => (string) now()->year,
        ]);

        $body = TokenReplacer::apply($template?->content ?? '', $tokens);

        // Bersihkan token yang tidak terselesaikan (mis. {{harga}} di halaman
        // yang datanya kosong) agar tidak tampil mentah ke pengunjung.
        $body = preg_replace('/\{\{\s*[a-zA-Z0-9_]+\s*\}\}/', '', $body) ?? $body;

        // Dukungan Blade opsional untuk template tingkat lanjut (RFP: mendukung Blade).
        // KEAMANAN: hanya jalan bila saklar .env (config cegu.template_blade) DAN
        // toggle Pengaturan sama-sama aktif — sehingga kompromi DB saja tidak
        // cukup untuk memicu eksekusi PHP lewat template.
        if (
            config('daya.template_blade', false) === true
            && Setting::get('template_blade_enabled') === '1'
            && str_contains($body, '@')
        ) {
            $body = Blade::render($body, ['page' => $page, 'tokens' => $tokens], deleteCachedView: false);
        }

        return [
            'page' => $page,
            'template' => $template,
            'body' => $body,
            'css' => $template?->css,
            'js' => $template?->js,
            'seo' => $seo,
            'wa' => $base['wa'],
        ];
    }

    /**
     * @return array<string,string>
     */
    private function baseTokens(Page $page): array
    {
        $settings = Setting::map();
        $layanan = $page->service->name ?? '';
        $kota = $page->city->name ?? '';
        $kecamatan = $page->district->name ?? '';
        $kelurahan = $page->village->name ?? '';

        // ROTATOR WA: kolom "whatsapp_number" boleh berisi BANYAK nomor
        // (dipisah baris baru, koma, atau titik-koma). Tiap halaman memilih satu
        // nomor secara DETERMINISTIK dari variation_seed-nya — jadi beban lead
        // terbagi merata ke semua nomor, konsisten dengan cache (halaman yang
        // sama selalu ke nomor yang sama), tanpa bergantung layanan pihak ketiga.
        $waNumbers = collect(preg_split('/[\r\n,;]+/', $settings['whatsapp_number'] ?? ''))
            ->map(fn ($n) => preg_replace('/\D/', '', (string) $n))
            ->filter()
            ->values();
        $waNumber = $waNumbers->isNotEmpty()
            ? $waNumbers[$page->variation_seed % $waNumbers->count()]
            : '';

        $waMessage = TokenReplacer::apply($settings['whatsapp_message'] ?? 'Halo, saya tertarik dengan layanan Anda.', [
            'layanan' => $layanan, 'kota' => $kota, 'kecamatan' => $kecamatan, 'kelurahan' => $kelurahan,
        ]);
        $waLink = 'https://wa.me/' . $waNumber . '?text=' . rawurlencode($waMessage);

        // Nilai dari CSV → di-escape karena di-import massal (anti-XSS).
        $base = [
            'layanan' => e($layanan),
            'kota' => e($kota),
            'kecamatan' => e($kecamatan),
            'kelurahan' => e($kelurahan),
            'brand' => e($settings['brand_name'] ?? ''),
            'wa' => $waLink,
            'wa_number' => $waNumber,
        ];

        // DATA LOKAL: kolom CSV opsional (harga, jumlah_tutor, landmark, ...)
        // menjadi token biasa → bisa dipakai di template maupun variasi konten.
        foreach (($page->extra ?? []) as $key => $value) {
            if (! array_key_exists($key, $base) && is_scalar($value)) {
                $base[$key] = e((string) $value);
            }
        }

        return $base;
    }

    private function resolveBlock(string $content, array $base): string
    {
        return TokenReplacer::apply($content, $base);
    }

    /**
     * @param array<int,string> $items
     * @return array<int,string>
     */
    private function resolveMany(array $items, array $base): array
    {
        return array_map(fn ($i) => TokenReplacer::apply($i, $base), $items);
    }

    /**
     * @param array<int,string> $items
     */
    private function htmlList(array $items, string $class): string
    {
        if (empty($items)) {
            return '';
        }
        $li = implode('', array_map(fn ($i) => '<li>' . e($i) . '</li>', $items));

        return "<ul class=\"{$class}\">{$li}</ul>";
    }

    /**
     * @param array<int,string> $items
     */
    private function htmlTestimonials(array $items): string
    {
        if (empty($items)) {
            return '';
        }
        $cards = implode('', array_map(
            fn ($i) => '<figure class="cegu-testi"><blockquote>' . e($i) . '</blockquote></figure>',
            $items
        ));

        return "<div class=\"cegu-testi-grid\">{$cards}</div>";
    }

    /**
     * @param array<int,array{question:string,answer:string}> $faqs
     */
    private function htmlFaq(array $faqs): string
    {
        if (empty($faqs)) {
            return '';
        }
        $items = implode('', array_map(function ($f) {
            return '<details class="cegu-faq-item"><summary>' . e($f['question']) . '</summary>'
                . '<div class="cegu-faq-answer">' . e($f['answer']) . '</div></details>';
        }, $faqs));

        return "<div class=\"cegu-faq\">{$items}</div>";
    }

    /**
     * @param array<int,array{label:string,url:string}> $crumbs
     */
    private function htmlBreadcrumb(array $crumbs): string
    {
        $parts = array_map(function ($c, $i) use ($crumbs) {
            $isLast = $i === count($crumbs) - 1;

            return $isLast
                ? '<span aria-current="page">' . e($c['label']) . '</span>'
                : '<a href="' . e($c['url']) . '">' . e($c['label']) . '</a>';
        }, $crumbs, array_keys($crumbs));

        return '<nav class="cegu-breadcrumb" aria-label="Breadcrumb">' . implode(' <span class="sep">/</span> ', $parts) . '</nav>';
    }

    /**
     * @param array{services:array,locations:array} $links
     */
    private function htmlInternalLinks(array $links): string
    {
        $render = function (string $title, array $items) {
            if (empty($items)) {
                return '';
            }
            $li = implode('', array_map(
                fn ($l) => '<li><a href="' . e($l['url']) . '">' . e($l['label']) . '</a></li>',
                $items
            ));

            return "<div class=\"cegu-links-col\"><h3>{$title}</h3><ul>{$li}</ul></div>";
        };

        $services = $render('Layanan Terkait', $links['services']);
        $locations = $render('Lokasi Terkait', $links['locations']);

        if ($services === '' && $locations === '') {
            return '';
        }

        return "<div class=\"cegu-internal-links\">{$services}{$locations}</div>";
    }

    /**
     * Gambar section (opsional). Mengembalikan string kosong bila URL tidak
     * diisi, sehingga section tampil normal tanpa gambar. Alt teks memuat
     * konteks lokasi untuk SEO. Lazy-load agar tidak memberatkan halaman.
     */
    /**
     * Galeri gambar (maks 6 slot dari Pengaturan). Setiap gambar mendapat alt
     * BERBEDA yang menyebut layanan + lokasi + urutan, sehingga satu set foto
     * yang sama tidak menghasilkan alt duplikat di jutaan halaman.
     */
    private function htmlGaleri(array $settings, Page $page): string
    {
        $layanan = $page->service->name ?? '';
        $kel = $page->village->name ?? '';
        $kec = $page->district->name ?? '';

        $items = [];
        for ($i = 1; $i <= 6; $i++) {
            $url = trim((string) ($settings["image_galeri_{$i}"] ?? ''));
            if ($url === '') {
                continue;
            }
            $alt = trim("{$layanan} {$kel} {$kec} - foto " . (count($items) + 1));
            $items[] = '<img src="' . e($url) . '" alt="' . e($alt) . '" loading="lazy"'
                . ' style="width:100%;height:100%;object-fit:cover;border-radius:var(--radius)">';
        }

        if ($items === []) {
            return '';
        }

        return '<div class="cegu-grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">'
            . implode('', array_map(fn ($img) => '<div style="aspect-ratio:4/3;overflow:hidden">' . $img . '</div>', $items))
            . '</div>';
    }

    private function htmlSectionImage(string $url, string $alt): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        return '<div class="cegu-section-img"><img src="' . e($url) . '" alt="' . e($alt)
            . '" loading="lazy" decoding="async"></div>';
    }

    /**
     * Blok "Fakta Lokal" dari data CSV opsional. Kunci yang dikenal mendapat
     * label ramah; kunci lain memakai judul dari nama kolomnya.
     *
     * @param array<string,mixed> $extra
     */
    private function htmlFaktaLokal(array $extra): string
    {
        if (empty($extra)) {
            return '';
        }

        $labels = [
            // umum (semua niche)
            'harga' => 'Harga mulai',
            'landmark' => 'Mudah dijangkau dari',
            'jadwal' => 'Jadwal',
            'stok' => 'Stok tersedia',
            'garansi' => 'Garansi',
            'pengiriman' => 'Pengiriman',
            'pengalaman' => 'Pengalaman',
            'jam_operasional' => 'Jam operasional',
            // pendidikan
            'jumlah_tutor' => 'Tenaga pengajar aktif',
            'sekolah' => 'Melayani sekitar sekolah',
            // properti
            'luas_tanah' => 'Luas tanah',
            'luas_bangunan' => 'Luas bangunan',
            'kamar' => 'Kamar tidur',
            'legalitas' => 'Legalitas',
            // produk/herbal
            'komposisi' => 'Komposisi',
            'izin_bpom' => 'Izin BPOM',
            'isi' => 'Isi kemasan',
        ];

        $items = '';
        foreach ($extra as $key => $value) {
            if (! is_scalar($value) || trim((string) $value) === '') {
                continue;
            }
            $label = $labels[$key] ?? ucwords(str_replace('_', ' ', (string) $key));
            $items .= '<li><strong>' . e($label) . ':</strong> ' . e((string) $value) . '</li>';
        }

        if ($items === '') {
            return '';
        }

        return '<ul class="cegu-fakta">' . $items . '</ul>';
    }

    private function htmlWaButton(string $waLink, string $label): string
    {
        return '<a class="cegu-btn green" href="' . e($waLink) . '" target="_blank" rel="noopener nofollow">'
            . '<span aria-hidden="true">💬</span> ' . e($label) . '</a>';
    }

    /**
     * Gambar hero. Bila URL kosong → string kosong (hero tetap rapi).
     * alt = judul hero (RFP/SEO: deskriptif, hindari gambar tanpa alt).
     */
    private function htmlHeroImage(?string $url, string $altFromHeadline): string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }

        return '<div class="cegu-hero-media"><img class="cegu-hero-img" src="' . e($url) . '" '
            . 'alt="' . e($altFromHeadline) . '" loading="eager" fetchpriority="high" decoding="async"></div>';
    }
}
