<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Services\PageRenderer;
use App\Support\RenderCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class PageController extends Controller
{
    public function __construct(private readonly PageRenderer $renderer)
    {
    }

    /**
     * Resolver URL pSEO.
     *  - 4 segmen: /{layanan}/{kota}/{kecamatan}/{kelurahan} → salespage.
     *  - 1–3 segmen: halaman kategori (internal linking) → memperkuat crawling.
     */
    public function show(Request $request, string $path)
    {
        $path = trim($path, '/');
        $segments = $path === '' ? [] : explode('/', $path);

        // HALAMAN STATIS (Tentang Kami, Kontak, dll) diperiksa lebih dulu.
        // Semuanya berbagi ruang URL satu segmen dengan hub layanan, jadi
        // pencocokannya dilakukan di sini agar tidak perlu rute tambahan yang
        // berpotensi menimpa catch-all pSEO.
        if (count($segments) === 1) {
            $static = \App\Models\SitePage::active()->where('slug', $segments[0])->first();
            if ($static) {
                return $this->staticPage($static);
            }
        }

        return match (count($segments)) {
            4 => $this->salespage($path),
            1, 2, 3 => app(CategoryController::class)->show($segments),
            default => abort(404),
        };
    }

    /**
     * Render halaman statis. Memakai layout yang sama dengan salespage agar
     * chrome (nav, footer, warna) konsisten, dan mengirim data SEO sendiri
     * supaya judul/deskripsinya unik.
     */
    private function staticPage(\App\Models\SitePage $page)
    {
        $brand = (string) \App\Models\Setting::get('brand_name', '');
        $desc = trim((string) $page->meta_description)
            ?: \Illuminate\Support\Str::limit(strip_tags((string) $page->content), 155, '…');

        $breadcrumb = [
            ['label' => 'Beranda', 'url' => url('/')],
            ['label' => $page->title, 'url' => $page->url()],
        ];

        $seo = [
            'title' => \Illuminate\Support\Str::limit($page->title, 60, '') . ($brand !== '' ? " | {$brand}" : ''),
            'description' => $desc,
            'canonical' => $page->url(),
            'h1' => $page->title,
            'breadcrumb' => $breadcrumb,
            'type' => 'website',
            'robots' => (string) \App\Models\Setting::get('default_robots', 'index,follow'),
        ];
        // HERO OTOMATIS: bila halaman belum punya gambar sendiri, ambil gambar
        // situs yang topiknya cocok dengan slug — mis. /tentang-kami memakai
        // gambar "Tentang", /layanan memakai gambar "Keunggulan".
        $heroOtomatis = $page->hero_image ?: $this->cocokkanGambarTopik($page->slug);
        if ($heroOtomatis) {
            $seo['image'] = $heroOtomatis;
            $seo['image_alt'] = $page->title;
        }

        // ---------- GAMBAR SITUS (dipakai bersama beranda & salespage) ----------
        // Halaman statis boleh MEMAKAI ULANG gambar yang sudah diunggah di
        // Pengaturan, sehingga admin tidak perlu mengunggah dua kali untuk
        // topik yang sama (mis. halaman "Tentang" memakai gambar tentang yang
        // juga tampil di salespage).
        $st = fn ($k) => trim((string) \App\Models\Setting::get($k, ''));
        $gambarSitus = [
            'situs_hero' => $st('hero_image'),
            'situs_keunggulan' => $st('image_keunggulan'),
            'situs_solusi' => $st('image_solusi'),
            'situs_proses' => $st('image_proses'),
            'situs_tentang' => $st('image_tentang'),
        ];

        // Token untuk konten halaman statis: {{brand}}, {{year}}, dan
        // {{gambar1}}..{{gambar4}} + {{galeri}} agar admin bisa MENYISIPKAN
        // gambar di posisi mana pun dalam tulisan.
        $gambar = $page->images();
        $tokenGambar = [];
        foreach ($gambar as $i => $g) {
            $tokenGambar['gambar' . ($i + 1)] =
                '<img src="' . e($g['url']) . '" alt="' . e($g['alt']) . '" loading="lazy"'
                . ' style="width:100%;height:auto;border-radius:var(--radius);margin:18px 0">';
        }
        $tokenGambar['galeri'] = $gambar === [] ? '' :
            '<div class="cegu-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">'
            . implode('', array_map(
                fn ($g) => '<div style="aspect-ratio:4/3;overflow:hidden;border-radius:var(--radius)">'
                    . '<img src="' . e($g['url']) . '" alt="' . e($g['alt']) . '" loading="lazy"'
                    . ' style="width:100%;height:100%;object-fit:cover"></div>',
                $gambar))
            . '</div>';

        // Token gambar situs: {{situs_tentang}} dsb. Alt disusun dari judul
        // halaman agar tetap deskriptif walau gambarnya dipakai berulang.
        foreach ($gambarSitus as $tk => $url) {
            $tokenGambar[$tk] = $url === '' ? '' :
                '<img src="' . e($url) . '" alt="' . e($page->title) . '" loading="lazy"'
                . ' style="width:100%;height:auto;border-radius:var(--radius);margin:18px 0">';
        }

        // Galeri situs (image_galeri_1..6 dari Pengaturan).
        $galSitus = [];
        for ($i = 1; $i <= 6; $i++) {
            $u = $st("image_galeri_{$i}");
            if ($u !== '') {
                $galSitus[] = '<div style="aspect-ratio:4/3;overflow:hidden;border-radius:var(--radius)">'
                    . '<img src="' . e($u) . '" alt="' . e($page->title . ' - foto ' . (count($galSitus) + 1)) . '"'
                    . ' loading="lazy" style="width:100%;height:100%;object-fit:cover"></div>';
            }
        }
        $tokenGambar['situs_galeri'] = $galSitus === [] ? '' :
            '<div class="cegu-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">'
            . implode('', $galSitus) . '</div>';

        $isi = \App\Services\TokenReplacer::apply((string) $page->content, $tokenGambar + [
            'brand' => e($brand),
            'year' => (string) now()->year,
        ]);
        $isi = preg_replace('/\{\{\s*[a-zA-Z0-9_]+\s*\}\}/', '', $isi) ?? $isi;

        return \Illuminate\Support\Facades\View::make('sitepage', [
            'page' => $page,
            'seo' => $seo,
            'breadcrumb' => $breadcrumb,
            'isi' => $isi,
            // Bila admin TIDAK memakai token gambar di konten, galeri
            // ditampilkan otomatis di bawah agar gambar tetap terpakai.
            'heroImage' => $heroOtomatis,
            'gambarSisa' => str_contains((string) $page->content, '{{gambar')
                || str_contains((string) $page->content, '{{galeri')
                || str_contains((string) $page->content, '{{situs_') ? [] : $gambar,
        ]);
    }

    /**
     * Cocokkan slug halaman statis dengan gambar situs yang topiknya sesuai.
     *
     * Tujuannya: admin cukup mengunggah gambar SEKALI di Pengaturan, lalu
     * halaman "Tentang Kami", "Layanan", dsb. otomatis memakainya tanpa perlu
     * mengisi ulang. Bila tidak ada yang cocok, kembalikan string kosong.
     */
    private function cocokkanGambarTopik(string $slug): string
    {
        $peta = [
            'image_tentang' => ['tentang', 'profil', 'about', 'kenali'],
            'image_keunggulan' => ['layanan', 'jasa', 'produk', 'program', 'keunggulan'],
            'image_proses' => ['proses', 'cara', 'alur', 'prosedur'],
            'image_solusi' => ['solusi', 'manfaat'],
        ];

        foreach ($peta as $settingKey => $kata) {
            foreach ($kata as $k) {
                if (str_contains($slug, $k)) {
                    $url = trim((string) \App\Models\Setting::get($settingKey, ''));
                    if ($url !== '') {
                        return $url;
                    }
                }
            }
        }

        // Cadangan terakhir: gambar hero situs.
        return trim((string) \App\Models\Setting::get('hero_image', ''));
    }

    private function salespage(string $path)
    {
        // Lookup ringan dulu (indexed unique `path`).
        $page = Page::where('path', $path)->first();
        if (! $page) {
            abort(404);
        }

        // Halaman ada tapi tidak published:
        //  - pernah tayang lalu dicabut (published_at terisi) → 410 Gone,
        //    agar Google cepat mencabutnya dari indeks (bukan sekadar 404).
        //  - belum pernah tayang (draft murni) → 404.
        if ($page->status !== Page::STATUS_PUBLISHED) {
            abort($page->published_at !== null ? 410 : 404);
        }

        $render = fn (): string => View::make('salespage', $this->renderer->render($page))->render();

        $ttl = RenderCache::ttl();
        $html = $ttl > 0
            ? cache()->remember(RenderCache::key($path), $ttl, $render)
            : $render();

        // Cache-Control agar Nginx/CDN dapat ikut menyimpan (lihat panduan).
        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=600');
    }
}
