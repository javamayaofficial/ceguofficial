<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Template;
use App\Services\PageRenderer;
use App\Support\RenderCache;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = Template::orderByDesc('is_active')->orderBy('name')->get();

        return view('admin.templates.index', compact('templates'));
    }

    public function create()
    {
        $template = new Template([
            'name' => 'Template Baru',
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

        return redirect()->route('admin.templates.edit', $template)->with('status', 'Template dibuat.');
    }

    public function update(Request $request, Template $template)
    {
        $template->update($this->validateData($request));
        $this->handleActivation($template, $request);
        Template::flushActiveCache();
        RenderCache::bump();

        return redirect()->route('admin.templates.edit', $template)->with('status', 'Template disimpan.');
    }

    /**
     * Muat template kerangka dari resources/templates/*.html ke database.
     *
     * Aman diulang: nama yang sudah ada dilewati, tidak menimpa template yang
     * pernah disunting manual di panel admin.
     */
    public function importKerangka()
    {
        $dir = resource_path('templates');
        if (! is_dir($dir)) {
            return back()->withErrors(['import' => 'Folder resources/templates tidak ditemukan di server.']);
        }

        $berkas = glob($dir . '/*.html') ?: [];
        if ($berkas === []) {
            return back()->withErrors(['import' => 'Tidak ada berkas template di resources/templates.']);
        }

        $label = [
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

        $dibuat = 0;
        $dilewati = 0;

        foreach ($berkas as $path) {
            $slug = pathinfo($path, PATHINFO_FILENAME);
            $nama = $label[$slug] ?? ucwords(str_replace('-', ' ', $slug));

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

    /**
     * Preview (RFP): render template terhadap halaman contoh tanpa menyimpan.
     */
    public function preview(Request $request, PageRenderer $renderer)
    {
        $content = (string) $request->input('content', '');
        $css = (string) $request->input('css', '');
        $js = (string) $request->input('js', '');

        $page = Page::with(['service', 'city', 'district', 'village'])->inRandomOrder()->first();
        if (! $page) {
            return response('<p style="font-family:sans-serif;padding:2rem">Belum ada halaman untuk di-preview. Import CSV & generate terlebih dahulu.</p>');
        }

        // Template sementara (tidak disimpan ke DB).
        $temp = new Template(['content' => $content, 'css' => $css, 'js' => $js]);
        $page->setRelation('template', $temp);

        $data = $renderer->render($page);

        return view('salespage', $data);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'css' => ['nullable', 'string'],
            'js' => ['nullable', 'string'],
        ]);
    }

    private function handleActivation(Template $template, Request $request): void
    {
        if ($request->boolean('activate')) {
            $template->makeActive();
        }
    }
}
