<?php

namespace App\Services;

use App\Models\City;
use App\Models\Faq;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Template;
use App\Support\SalespageStyles;
use App\Support\WaLink;

class HomeTemplateRenderer
{
    private const SEED = 20260101;

    public function __construct(private readonly ContentRepository $content)
    {
    }

    public static function aktif(): bool
    {
        if (Template::active(Template::TYPE_HOME) !== null) {
            return true;
        }

        return Setting::get('home_use_template') === '1'
            && Template::active(Template::TYPE_SALESPAGE) !== null;
    }

    private static function templateBeranda(): ?Template
    {
        return Template::active(Template::TYPE_HOME)
            ?? Template::active(Template::TYPE_SALESPAGE);
    }

    public function render(): array
    {
        $template = self::templateBeranda();
        $engine = VariationEngine::forSeed(self::SEED);
        $tokens = $this->tokens($engine);

        $body = TokenReplacer::apply($template?->content ?? '', $tokens);
        $body = preg_replace('/\{\{\s*[a-zA-Z0-9_]+\s*\}\}/', '', $body) ?? $body;

        $brand = (string) Setting::get('brand_name', '');
        $tagline = (string) Setting::get('tagline', '');

        return [
            'body' => $body,
            'css' => SalespageStyles::base() . "\n" . ($template?->css ?? ''),
            'js' => $template?->js ?? '',
            'seo' => [
                'title' => trim($brand . ($tagline !== '' ? " — {$tagline}" : '')) ?: 'Beranda',
                'description' => $tagline,
                'canonical' => url('/'),
                'h1' => $brand,
                'type' => 'website',
                'robots' => (string) Setting::get('default_robots', 'index,follow'),
            ],
        ];
    }

    private function tokens(VariationEngine $engine): array
    {
        $brand = (string) Setting::get('brand_name', '');
        $area = $this->areaLabel();
        $layanan = $this->layananLabel();
        $blocks = $this->content->blocksBySection();

        $pick = function (string $section) use ($engine, $blocks, $layanan, $area, $brand) {
            $teks = $engine->pick($section, $blocks[$section] ?? []) ?? '';

            return TokenReplacer::apply($teks, [
                'layanan' => e($layanan), 'kota' => e($area),
                'kecamatan' => e($area), 'kelurahan' => e($area), 'brand' => e($brand),
            ]);
        };

        $list = function (string $section, string $cls, int $n) use ($engine, $blocks, $layanan, $area, $brand) {
            $items = $engine->pickMany($section, $blocks[$section] ?? [], $n);
            if ($items === []) {
                return '';
            }
            $html = '';
            foreach ($items as $it) {
                $isi = TokenReplacer::apply($it, [
                    'layanan' => e($layanan), 'kota' => e($area),
                    'kecamatan' => e($area), 'kelurahan' => e($area), 'brand' => e($brand),
                ]);
                $html .= '<div class="cegu-tile"><p>' . $isi . '</p></div>';
            }

            return '<div class="' . $cls . '">' . $html . '</div>';
        };

        return [
            'brand' => e($brand),
            'layanan' => e($layanan),
            'kota' => e($area),
            'kecamatan' => e($area),
            'kelurahan' => e($area),
            'wa' => e(WaLink::generic($brand)),
            'breadcrumb' => '',
            'fakta_lokal' => '',
            'hero' => $pick('hero'),
            'intro' => $pick('intro'),
            'cta' => $pick('cta'),
            'about' => $pick('about'),
            'summary' => $pick('summary_open'),
            'usp_list' => $list('usp', 'cegu-grid', 6),
            'pain_point_list' => $list('pain_point', 'cegu-grid', 4),
            'solusi_list' => $list('solusi', 'cegu-grid', 4),
            'testimoni_list' => $list('testimoni', 'cegu-testi-grid', 3),
            'faq' => $this->faqHtml(),
            'internal_links' => $this->linksHtml(),
            'daftar_kota' => $this->kotaHtml(),
            'daftar_layanan' => $this->layananHtml(),
            'hero_image' => $this->img('hero_image', $brand),
            'gambar_keunggulan' => $this->img('image_keunggulan', $layanan),
            'gambar_solusi' => $this->img('image_solusi', $layanan),
            'gambar_proses' => $this->img('image_proses', $layanan),
            'gambar_tentang' => $this->img('image_tentang', $brand),
            'galeri' => $this->galeriHtml($brand),
            'katalog_layanan' => $this->katalogHtml($brand),
            'kredensial' => $this->kredensialHtml(),
        ];
    }

    private function areaLabel(): string
    {
        $manual = trim((string) Setting::get('home_area_label', ''));
        if ($manual !== '') {
            return $manual;
        }

        $kota = City::whereIn('id', \App\Models\Page::published()->select('city_id')->distinct())
            ->orderBy('name')->pluck('name');

        if ($kota->count() === 1) {
            return (string) $kota->first();
        }

        return $kota->count() > 1 ? 'wilayah layanan kami' : 'wilayah Anda';
    }

    private function layananLabel(): string
    {
        $manual = trim((string) Setting::get('home_layanan_label', ''));
        if ($manual !== '') {
            return $manual;
        }

        return (string) (Service::where('is_active', true)->orderBy('name')->value('name') ?: 'layanan kami');
    }

    private function img(string $key, string $alt): string
    {
        $url = trim((string) Setting::get($key, ''));
        if ($url === '') {
            return '';
        }

        return '<img src="' . e($url) . '" alt="' . e($alt) . '" loading="lazy" class="cegu-section-img"'
            . ' style="width:100%;height:auto;border-radius:var(--radius)">';
    }

    private function galeriHtml(string $alt): string
    {
        $items = '';
        $n = 0;
        for ($i = 1; $i <= 6; $i++) {
            $u = trim((string) Setting::get("image_galeri_{$i}", ''));
            if ($u === '') {
                continue;
            }
            $n++;
            $items .= '<div style="aspect-ratio:4/3;overflow:hidden;border-radius:var(--radius)">'
                . '<img src="' . e($u) . '" alt="' . e($alt . ' - foto ' . $n) . '" loading="lazy"'
                . ' style="width:100%;height:100%;object-fit:cover"></div>';
        }

        return $items === '' ? '' :
            '<div class="cegu-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">' . $items . '</div>';
    }

    private function katalogHtml(string $brand): string
    {
        $daftar = Service::where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')
            ->get(['name', 'slug', 'price_from', 'description', 'image']);

        if ($daftar->isEmpty()) {
            return '';
        }

        $wa = WaLink::generic($brand);
        $html = '<div class="cegu-grid">';
        foreach ($daftar as $svc) {
            $html .= '<div class="cegu-tile">';
            if ($svc->image) {
                $html .= '<img src="' . e($svc->image) . '" alt="' . e($svc->name) . '" loading="lazy"'
                    . ' style="width:100%;height:130px;object-fit:cover;border-radius:8px;margin-bottom:10px">';
            }
            $html .= '<h3>' . e($svc->name) . '</h3>';
            if ($svc->description) {
                $html .= '<p>' . e($svc->description) . '</p>';
            }
            if ($svc->price_from) {
                $html .= '<p style="font-weight:700;margin:8px 0 4px">Mulai dari ' . e($svc->price_from) . '</p>';
            }
            $html .= '<p style="margin-top:10px"><a href="' . e(url('/' . $svc->slug)) . '">Lihat detail</a>';
            if ($wa !== '#') {
                $html .= ' &nbsp;·&nbsp; <a href="' . e($wa) . '" target="_blank" rel="noopener nofollow">Tanya via WhatsApp</a>';
            }
            $html .= '</p></div>';
        }

        return $html . '</div>';
    }

    private function kredensialHtml(): string
    {
        $items = [];
        for ($i = 1; $i <= 6; $i++) {
            $img = trim((string) Setting::get("kredensial_{$i}_img", ''));
            $label = trim((string) Setting::get("kredensial_{$i}_label", ''));
            if ($img === '' && $label === '') {
                continue;
            }
            $alt = $label !== '' ? $label : 'Kredensial ' . (count($items) + 1);
            $isi = $img !== '' ? '<img src="' . e($img) . '" alt="' . e($alt) . '" loading="lazy" style="max-height:70px;width:auto">' : '';
            $isi .= $label !== '' ? '<div style="font-size:.82rem;margin-top:6px">' . e($label) . '</div>' : '';
            $items[] = '<div style="text-align:center;padding:10px">' . $isi . '</div>';
        }

        return $items === [] ? '' :
            '<div style="display:flex;flex-wrap:wrap;gap:18px;justify-content:center;align-items:center">' . implode('', $items) . '</div>';
    }

    private function faqHtml(): string
    {
        $faqs = Faq::where('is_active', true)->orderBy('sort_order')->limit(8)->get();
        if ($faqs->isEmpty()) {
            return '';
        }

        $html = '<div class="cegu-faq">';
        foreach ($faqs as $f) {
            $html .= '<details class="cegu-faq-item"><summary>' . e($f->question) . '</summary>'
                . '<div class="cegu-faq-answer">' . e($f->answer) . '</div></details>';
        }

        return $html . '</div>';
    }

    private function kotaHtml(): string
    {
        $first = Service::where('is_active', true)
            ->whereHas('pages', fn ($q) => $q->published())->orderBy('name')->first();

        if (! $first) {
            return '';
        }

        $kota = City::whereIn('id', \App\Models\Page::published()->select('city_id')->distinct())
            ->orderBy('name')->get(['name', 'slug']);

        if ($kota->isEmpty()) {
            return '';
        }

        $html = '<div class="cegu-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">';
        foreach ($kota as $c) {
            $html .= '<a class="cegu-tile" style="text-decoration:none;display:block"'
                . ' href="' . e(url('/' . $first->slug . '/' . $c->slug)) . '">'
                . '<h3 style="margin:0">' . e($c->name) . '</h3></a>';
        }

        return $html . '</div>';
    }

    private function layananHtml(): string
    {
        $items = Service::where('is_active', true)
            ->whereHas('pages', fn ($q) => $q->published())
            ->orderBy('name')->get(['name', 'slug']);

        if ($items->isEmpty()) {
            return '';
        }

        $html = '<div class="cegu-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr))">';
        foreach ($items as $service) {
            $html .= '<a class="cegu-tile" style="text-decoration:none;display:block" href="' . e(url('/' . $service->slug)) . '">'
                . '<h3 style="margin:0">' . e($service->name) . '</h3></a>';
        }

        return $html . '</div>';
    }

    private function linksHtml(): string
    {
        return $this->kotaHtml() . $this->layananHtml();
    }
}
