<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Template;
use App\Services\PageRenderer;
use App\Services\TemplateValidator;
use App\Support\RenderCache;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TemplateController extends Controller
{
    public function index()
    {
        return view('admin.templates.index', [
            'salespage' => Template::where('type', Template::TYPE_SALESPAGE)->orderByDesc('is_active')->orderBy('name')->get(),
            'home' => Template::where('type', Template::TYPE_HOME)->orderByDesc('is_active')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        $template = new Template([
            'name' => 'Template Baru',
            'type' => Template::TYPE_SALESPAGE,
            'content' => "{{breadcrumb}}\n<h1>{{hero}}</h1>\n{{wa_button}}\n<p>{{intro}}</p>",
        ]);

        return view('admin.templates.edit', ['template' => $template, 'isNew' => true]);
    }

    public function edit(Template $template)
    {
        return view('admin.templates.edit', ['template' => $template, 'isNew' => false]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $template = Template::create($data);
        $this->handleActivation($template, $request);
        RenderCache::bump();
        $this->simpanPeriksa($data['content'], $data['type']);

        return redirect()->route('admin.templates.edit', $template)->with('status', 'Template dibuat.');
    }

    public function update(Request $request, Template $template)
    {
        $data = $this->validateData($request);
        $template->update($data);
        $this->handleActivation($template, $request);
        Template::flushActiveCache();
        RenderCache::bump();
        $this->simpanPeriksa($template->content, $template->type);

        return redirect()->route('admin.templates.edit', $template)->with('status', 'Template disimpan.');
    }

    public function importKerangka()
    {
        $dir = resource_path('templates');
        if (! is_dir($dir)) {
            return back()->withErrors(['import' => 'Folder resources/templates tidak ditemukan di server.']);
        }

        $berkas = array_merge(
            glob($dir . '/*.html') ?: [],
            glob($dir . '/home/*.html') ?: [],
        );
        if ($berkas === []) {
            return back()->withErrors(['import' => 'Tidak ada berkas template di resources/templates.']);
        }

        $labelSales = [
            '01-aida' => 'AIDA — Attention, Interest, Desire, Action',
            '02-pas' => 'PAS — Problem, Agitate, Solution',
            '03-bab' => 'BAB — Before, After, Bridge',
            '04-4p' => '4P — Promise, Picture, Proof, Push',
            '05-quest' => 'QUEST — Qualify, Understand, Educate, Stimulate, Transition',
            '06-tofu' => 'TOFU — Awareness (audiens baru)',
            '07-mofu' => 'MOFU — Pertimbangan',
            '08-bofu' => 'BOFU — Siap membeli / closing',
            '09-vsl' => 'VSL — Video Sales Letter',
            '10-advertorial' => 'Advertorial — Soft selling ala artikel',
            '11-longform' => 'Long Form — Penjualan mendalam',
            '12-fsp' => 'FSP — Fakta, Story, Penawaran',
        ];

        $labelHome = [
            '01-navigasi' => 'Beranda: Navigasi — pusat penjelajahan (disarankan)',
            '02-penjualan' => 'Beranda: Penjualan — beranda sebagai penawaran',
            '03-ringkas' => 'Beranda: Ringkas — situs sederhana',
            '04-korporat' => 'Beranda: Korporat — profil perusahaan',
            '05-katalog' => 'Beranda: Katalog — produk/layanan di depan',
            '06-cerita' => 'Beranda: Cerita — tentang kami lebih dulu',
            '07-lokal' => 'Beranda: Lokal — wilayah layanan ditonjolkan',
            '08-konversi' => 'Beranda: Konversi — fokus tombol WhatsApp',
            '09-otoritas' => 'Beranda: Otoritas — kredensial di depan (hukum/kesehatan)',
            '10-portofolio' => 'Beranda: Portofolio — galeri hasil kerja',
            '11-edukasi' => 'Beranda: Edukasi — menjawab pertanyaan lebih dulu',
            '12-daftar' => 'Beranda: Daftar — direktori layanan & wilayah',
        ];

        $dibuat = 0;
        $dilewati = 0;

        foreach ($berkas as $path) {
            $slug = pathinfo($path, PATHINFO_FILENAME);
            $isHome = str_contains(str_replace('\\', '/', $path), '/home/');
            $nama = $isHome
                ? ($labelHome[$slug] ?? 'Beranda: ' . ucwords(str_replace('-', ' ', $slug)))
                : ($labelSales[$slug] ?? ucwords(str_replace('-', ' ', $slug)));

            if (Template::where('name', $nama)->exists()) {
                $dilewati++;
                continue;
            }

            $isi = (string) file_get_contents($path);
            if (trim($isi) === '') {
                continue;
            }

            Template::create([
                'name' => $nama,
                'type' => $isHome ? Template::TYPE_HOME : Template::TYPE_SALESPAGE,
                'content' => $isi,
                'is_active' => false,
            ]);
            $dibuat++;
        }

        $pesan = "{$dibuat} template kerangka dimuat.";
        if ($dilewati > 0) {
            $pesan .= " {$dilewati} dilewati karena sudah ada.";
        }
        $pesan .= ' Semuanya nonaktif — tekan Aktifkan pada yang ingin dipakai.';

        return back()->with('status', $pesan);
    }

    public function activate(Template $template)
    {
        $template->makeActive();
        RenderCache::bump();

        return back()->with('status', "Template \"{$template->name}\" diaktifkan untuk semua halaman.");
    }

    public function destroy(Template $template)
    {
        if ($template->is_active) {
            return back()->withErrors(['template' => 'Tidak bisa menghapus template yang sedang aktif.']);
        }
        $template->delete();

        return redirect()->route('admin.templates.index')->with('status', 'Template dihapus.');
    }

    public function preview(Request $request, PageRenderer $renderer)
    {
        $content = (string) $request->input('content', '');
        $css = (string) $request->input('css', '');
        $js = (string) $request->input('js', '');

        $page = Page::with(['service', 'city', 'district', 'village'])->inRandomOrder()->first();
        if (! $page) {
            return response('<p style="font-family:sans-serif;padding:2rem">Belum ada halaman untuk di-preview. Import CSV & generate terlebih dahulu.</p>');
        }

        $temp = new Template([
            'type' => (string) $request->input('type', Template::TYPE_SALESPAGE),
            'content' => $content,
            'css' => $css,
            'js' => $js,
        ]);
        $page->setRelation('template', $temp);

        $data = $renderer->render($page);

        return view('salespage', $data);
    }

    private function simpanPeriksa(string $konten, string $type): void
    {
        $hasil = TemplateValidator::periksa($konten, $type);
        if ($hasil !== []) {
            session()->flash('periksa_template', $hasil);
        }
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', Rule::in(array_keys(Template::TYPES))],
            'content' => ['required', 'string'],
            'css' => ['nullable', 'string'],
            'js' => ['nullable', 'string'],
        ]);

        $data['type'] = $data['type'] ?? Template::TYPE_SALESPAGE;

        return $data;
    }

    private function handleActivation(Template $template, Request $request): void
    {
        if ($request->boolean('activate')) {
            $template->makeActive();
        }
    }
}
