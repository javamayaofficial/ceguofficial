<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\SitePage;
use App\Support\PagePresets;
use App\Support\RenderCache;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Kelola halaman statis (Tentang Kami, Layanan, Kontak, Kebijakan Privasi, dll).
 *
 * Halaman ini muncul sebagai MENU NAVIGASI sungguhan dengan URL sendiri —
 * menggantikan anchor (#layanan) yang hanya menggulung di halaman yang sama.
 */
class SitePageController extends Controller
{
    public function index()
    {
        return view('admin.sitepages.index', [
            'pages' => SitePage::orderBy('sort_order')->orderBy('title')->get(),
            'presets' => PagePresets::all(),
        ]);
    }

    /**
     * Muat paket halaman siap pakai sesuai jenis usaha.
     *
     * Halaman statis sengaja TIDAK dibuat saat instalasi karena tiap produk
     * punya struktur menu berbeda. Operator memilih paket setelah domain
     * terpasang, lalu menyunting isinya.
     */
    public function loadPreset(Request $request)
    {
        $data = $request->validate([
            'preset' => ['required', 'string', Rule::in(array_keys(PagePresets::all()))],
        ]);

        $paket = PagePresets::all()[$data['preset']];
        $dibuat = 0;
        $dilewati = [];
        $urut = (int) SitePage::max('sort_order');

        foreach ($paket['pages'] as $p) {
            // Jangan menimpa halaman yang sudah ada / bentrok dengan layanan.
            if (SitePage::where('slug', $p['slug'])->exists()
                || Service::where('slug', $p['slug'])->exists()) {
                $dilewati[] = $p['slug'];
                continue;
            }

            SitePage::create($p + ['sort_order' => ++$urut]);
            $dibuat++;
        }

        $this->segarkan();

        $pesan = "Paket \"{$paket['label']}\" dimuat: {$dibuat} halaman dibuat.";
        if ($dilewati !== []) {
            $pesan .= ' Dilewati (sudah ada): ' . implode(', ', $dilewati) . '.';
        }
        $pesan .= ' Sunting isinya dengan data bisnis yang sebenarnya sebelum dipublikasikan.';

        return back()->with('status', $pesan);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $page = SitePage::create($data);
        $this->segarkan();
        $this->pingIndexNow($page);

        return back()->with('status', "Halaman \"{$data['title']}\" dibuat. URL: /{$data['slug']}");
    }

    public function update(Request $request, SitePage $sitepage)
    {
        $sitepage->update($this->validated($request, $sitepage->id));
        $this->segarkan();
        $this->pingIndexNow($sitepage);

        return back()->with('status', 'Halaman diperbarui.');
    }

    public function destroy(SitePage $sitepage)
    {
        $sitepage->delete();
        $this->segarkan();

        return back()->with('status', 'Halaman dihapus.');
    }

    /**
     * @return array<string,mixed>
     */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('site_pages', 'slug')->ignore($ignoreId)],
            'menu_label' => ['nullable', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'hero_image' => ['nullable', 'string', 'max:500'],
            'image_1' => ['nullable', 'string', 'max:500'],
            'image_1_alt' => ['nullable', 'string', 'max:190'],
            'image_2' => ['nullable', 'string', 'max:500'],
            'image_2_alt' => ['nullable', 'string', 'max:190'],
            'image_3' => ['nullable', 'string', 'max:500'],
            'image_3_alt' => ['nullable', 'string', 'max:190'],
            'image_4' => ['nullable', 'string', 'max:500'],
            'image_4_alt' => ['nullable', 'string', 'max:190'],
            'content' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $data['slug'] = Str::slug($data['slug'] ?: $data['title']);

        // Cegah bentrok dengan rute sistem.
        if (in_array($data['slug'], SitePage::RESERVED, true)) {
            abort(422, "Slug \"{$data['slug']}\" dipakai sistem. Pilih slug lain.");
        }

        // Cegah bentrok dengan hub layanan pSEO (keduanya URL satu segmen).
        if (Service::where('slug', $data['slug'])->exists()) {
            abort(422, "Slug \"{$data['slug']}\" sudah dipakai halaman layanan. Pilih slug lain.");
        }

        $data['show_in_nav'] = $request->boolean('show_in_nav');
        $data['show_in_footer'] = $request->boolean('show_in_footer');
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    private function segarkan(): void
    {
        cache()->forget('daya.nav.pages');
        RenderCache::bump();
    }

    /**
     * Beritahu IndexNow tentang halaman statis yang baru/berubah, agar cepat
     * terindeks seperti salespage. No-op bila INDEXNOW_KEY kosong.
     */
    private function pingIndexNow(SitePage $page): void
    {
        if (! $page->is_active || ! \App\Services\IndexNowService::isEnabled()) {
            return;
        }

        try {
            app(\App\Services\IndexNowService::class)->submit([$page->url()]);
        } catch (\Throwable $e) {
            // Kegagalan IndexNow tidak boleh menghalangi penyimpanan halaman.
        }
    }
}
