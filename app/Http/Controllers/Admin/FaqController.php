<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Service;
use App\Services\ContentRepository;
use App\Support\RenderCache;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index()
    {
        $faqs = Faq::with('service:id,name')->orderBy('service_id')->orderBy('sort_order')->get();
        $services = Service::orderBy('name')->get(['id', 'name']);

        return view('admin.faqs.index', compact('faqs', 'services'));
    }

    public function store(Request $request)
    {
        Faq::create($this->validateData($request));
        ContentRepository::flushCache();
        RenderCache::bump();

        return back()->with('status', 'FAQ ditambahkan.');
    }

    public function update(Request $request, Faq $faq)
    {
        $faq->update($this->validateData($request));
        ContentRepository::flushCache();
        RenderCache::bump();

        return back()->with('status', 'FAQ diperbarui.');
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();
        ContentRepository::flushCache();
        RenderCache::bump();

        return back()->with('status', 'FAQ dihapus.');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'service_id' => ['nullable', 'exists:services,id'],
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active', true);
        $data['service_id'] = $data['service_id'] ?: null;

        return $data;
    }

    /**
     * Import massal FAQ dari CSV (kolom: question,answer[,layanan]).
     * Kolom layanan kosong = FAQ global; berisi nama/slug layanan = FAQ khusus
     * layanan itu (baris gagal bila layanan tidak ditemukan).
     */
    public function importCsv(\Illuminate\Http\Request $request)
    {
        $request->validate(['csv' => ['required', 'file', 'extensions:csv,txt', 'max:10240']]);

        $handle = fopen($request->file('csv')->getRealPath(), 'r');
        $reader = new \App\Support\CsvReader($handle);
        $header = $reader->header();
        if (array_diff(['question', 'answer'], $header)) {
            fclose($handle);

            return back()->withErrors(['csv' => 'Header CSV wajib: question,answer (kolom layanan opsional).']);
        }
        $idx = array_flip($header);

        $added = 0;
        $skipped = 0;
        $notFound = [];
        $sort = (int) Faq::max('sort_order');
        while (($row = $reader->row()) !== false) {
            if ($row === [null]) {
                continue;
            }
            $q = trim((string) ($row[$idx['question']] ?? ''));
            $a = trim((string) ($row[$idx['answer']] ?? ''));
            if ($q === '' || $a === '') {
                $skipped++;

                continue;
            }

            $serviceId = null;
            $layanan = trim((string) ($row[$idx['layanan'] ?? -1] ?? ''));
            if ($layanan !== '') {
                $service = \App\Models\Service::where('name', $layanan)
                    ->orWhere('slug', \Illuminate\Support\Str::slug($layanan))
                    ->first();
                if (! $service) {
                    $notFound[$layanan] = true;
                    $skipped++;

                    continue;
                }
                $serviceId = $service->id;
            }

            $faq = Faq::firstOrCreate(
                ['service_id' => $serviceId, 'question' => $q],
                ['answer' => $a, 'sort_order' => ++$sort, 'is_active' => true]
            );
            $faq->wasRecentlyCreated ? $added++ : $skipped++;
        }
        fclose($handle);

        ContentRepository::flushCache();
        \App\Support\RenderCache::bump();

        $msg = "Import FAQ selesai: +{$added} baru, {$skipped} dilewati.";
        if ($notFound !== []) {
            $msg .= ' Layanan tidak ditemukan (import lokasinya dulu): ' . implode(', ', array_keys($notFound)) . '.';
        }

        return back()->with('status', $msg);
    }
}
