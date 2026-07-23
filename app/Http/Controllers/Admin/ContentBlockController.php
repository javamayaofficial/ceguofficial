<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateContentBlocksJob;
use App\Models\ContentBlock;
use App\Models\Setting;
use App\Services\Ai\AiClientFactory;
use App\Services\ContentPackService;
use App\Services\ContentRepository;
use App\Support\AiFillProgress;
use App\Support\RenderCache;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContentBlockController extends Controller
{
    public function index(Request $request)
    {
        $section = $request->query('section', 'hero');
        if (! in_array($section, ContentBlock::SECTIONS, true)) {
            $section = 'hero';
        }

        $blocks = ContentBlock::section($section)->orderBy('id')->get();
        $counts = ContentBlock::selectRaw('section, count(*) as total')->groupBy('section')->pluck('total', 'section');

        return view('admin.content.index', [
            'sections' => ContentBlock::SECTIONS,
            'section' => $section,
            'packs' => (new ContentPackService())->available(),
            'blocks' => $blocks,
            'counts' => $counts,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'section' => ['required', Rule::in(ContentBlock::SECTIONS)],
            'content' => ['required', 'string'],
            'weight' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $data['weight'] = $data['weight'] ?? 1;

        ContentBlock::create($data);
        ContentRepository::flushCache();
        RenderCache::bump();

        return back()->with('status', 'Variasi konten ditambahkan.');
    }

    public function update(Request $request, ContentBlock $contentBlock)
    {
        $data = $request->validate([
            'content' => ['required', 'string'],
            'weight' => ['nullable', 'integer', 'min:1', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $contentBlock->update([
            'content' => $data['content'],
            'weight' => $data['weight'] ?? $contentBlock->weight,
            'is_active' => $request->boolean('is_active'),
        ]);
        ContentRepository::flushCache();
        RenderCache::bump();

        return back()->with('status', 'Variasi konten diperbarui.');
    }

    public function destroy(ContentBlock $contentBlock)
    {
        $section = $contentBlock->section;
        $contentBlock->delete();
        ContentRepository::flushCache();
        RenderCache::bump();

        return redirect()->route('admin.content.index', ['section' => $section])->with('status', 'Variasi dihapus.');
    }

    /**
     * Muat Paket Konten Awal (multi-niche): pendidikan, jasa umum, herbal,
     * properti, dst. Aditif — tidak menghapus variasi yang sudah ada.
     */
    public function loadPack(\Illuminate\Http\Request $request, ContentPackService $packs)
    {
        $slug = (string) $request->validate([
            'pack' => ['required', 'string'],
        ])['pack'];

        if (! array_key_exists($slug, $packs->available())) {
            return back()->withErrors(['pack' => 'Paket konten tidak ditemukan.']);
        }

        $result = $packs->load($slug);

        return back()->with('status', "Paket dimuat: +{$result['blocks']} variasi, +{$result['faqs']} FAQ. Variasi lama tetap dipertahankan.");
    }

    /**
     * Import massal variasi konten dari CSV (kolom: section,content[,weight]).
     * Memudahkan penulisan ratusan variasi di Google Sheets/Excel sekaligus.
     * Idempoten: baris yang persis sama dilewati.
     */
    public function importCsv(\Illuminate\Http\Request $request)
    {
        $request->validate(['csv' => ['required', 'file', 'extensions:csv,txt', 'max:10240']]);

        $handle = fopen($request->file('csv')->getRealPath(), 'r');
        $reader = new \App\Support\CsvReader($handle);
        $header = $reader->header();
        if (array_diff(['section', 'content'], $header)) {
            fclose($handle);

            return back()->withErrors(['csv' => 'Header CSV wajib: section,content (kolom weight opsional).']);
        }
        $idx = array_flip($header);

        $added = 0;
        $skipped = 0;
        $badSection = [];
        while (($row = $reader->row()) !== false) {
            if ($row === [null]) {
                continue;
            }
            $section = strtolower(trim((string) ($row[$idx['section']] ?? '')));
            $content = trim((string) ($row[$idx['content']] ?? ''));
            if ($section === '' || $content === '') {
                $skipped++;

                continue;
            }
            if (! in_array($section, ContentBlock::SECTIONS, true)) {
                $badSection[$section] = true;
                $skipped++;

                continue;
            }
            $weight = max(1, min(100, (int) ($row[$idx['weight'] ?? -1] ?? 1) ?: 1));
            $block = ContentBlock::firstOrCreate(
                ['section' => $section, 'content' => $content],
                ['weight' => $weight, 'is_active' => true]
            );
            $block->wasRecentlyCreated ? $added++ : $skipped++;
        }
        fclose($handle);

        ContentRepository::flushCache();
        \App\Support\RenderCache::bump();

        $msg = "Import variasi selesai: +{$added} baru, {$skipped} dilewati (duplikat/kosong).";
        if ($badSection !== []) {
            $msg .= ' Section tidak dikenal: ' . implode(', ', array_keys($badSection))
                . '. Yang valid: ' . implode(', ', ContentBlock::SECTIONS) . '.';
        }

        return back()->with('status', $msg);
    }

    /**
     * ISI OTOMATIS DENGAN AI: dispatch job yang meng-generate variasi + FAQ
     * sampai semua indikator kesehatan hijau. Kunci API diambil dari .env.
     */
    public function aiFill(Request $request)
    {
        if (! AiClientFactory::isConfigured()) {
            return back()->withErrors([
                'ai' => 'Kunci API AI belum diatur. Isi AI_DRIVER, AI_API_KEY, dan AI_MODEL di file .env lalu jalankan: php artisan config:clear.',
            ]);
        }

        if (AiFillProgress::isRunning()) {
            return back()->with('status', 'Proses AI sedang berjalan. Tunggu hingga selesai.');
        }

        $data = $request->validate([
            'business' => ['required', 'string', 'max:200'],
            'keywords' => ['nullable', 'string', 'max:1000'],
            'tone' => ['nullable', 'string', 'max:120'],
            'multiplier' => ['nullable', 'numeric', 'min:1', 'max:3'],
            'include_faq' => ['nullable', 'boolean'],
        ]);

        $context = [
            'brand' => (string) Setting::get('brand_name', ''),
            'business' => $data['business'],
            'keywords' => $data['keywords'] ?? '',
            'tone' => $data['tone'] ?? '',
        ];

        GenerateContentBlocksJob::dispatch(
            $context,
            $request->boolean('include_faq'),
            (float) ($data['multiplier'] ?? 1.0),
        );

        return back()->with('status', 'Proses AI dimulai. Pastikan worker antrian jalan (php artisan queue:work). Progres tampil di bawah — refresh berkala.');
    }

    /**
     * Status proses AI (untuk polling ringan dari halaman admin).
     */
    public function aiStatus()
    {
        return response()->json(AiFillProgress::get());
    }
}
