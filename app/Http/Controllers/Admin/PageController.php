<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\PublishPagesJob;
use App\Models\ImportBatch;
use App\Models\Page;
use App\Models\Template;
use App\Services\SitemapService;
use App\Support\PublishControl;
use App\Support\RenderCache;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $q = $request->query('q');

        $pages = Page::query()
            ->with(['service:id,name', 'city:id,name', 'template:id,name'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($q, fn ($query) => $query->where('path', 'like', '%' . $q . '%'))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $batches = ImportBatch::latest()->get(['id', 'original_filename']);

        return view('admin.pages.index', [
            'pages' => $pages,
            'status' => $status,
            'q' => $q,
            'batches' => $batches,
            'publishState' => PublishControl::state(),
            'publishMeta' => PublishControl::meta(),
            'templates' => Template::where('type', Template::TYPE_SALESPAGE)->orderBy('name')->get(['id', 'name', 'is_active']),
            'kotaList' => \App\Models\City::orderBy('name')->get(['id', 'name']),
            'layananList' => \App\Models\Service::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function terapkanTemplate(Request $request)
    {
        $data = $request->validate([
            'template_id' => ['nullable', 'integer', 'exists:templates,id'],
            'lingkup' => ['required', 'string', 'in:terpilih,kota,layanan,semua'],
            'page_ids' => ['nullable', 'array'],
            'page_ids.*' => ['integer'],
            'city_id' => ['nullable', 'integer'],
            'service_id' => ['nullable', 'integer'],
        ]);

        $templateId = $data['template_id'] ?: null;
        $query = Page::query();
        $keterangan = '';

        switch ($data['lingkup']) {
            case 'terpilih':
                $ids = array_filter((array) ($data['page_ids'] ?? []));
                if ($ids === []) {
                    return back()->withErrors(['template' => 'Belum ada halaman yang dicentang.']);
                }
                $query->whereIn('id', $ids);
                $keterangan = count($ids) . ' halaman terpilih';
                break;
            case 'kota':
                if (empty($data['city_id'])) {
                    return back()->withErrors(['template' => 'Pilih kota terlebih dahulu.']);
                }
                $query->where('city_id', $data['city_id']);
                $keterangan = 'semua halaman di kota terpilih';
                break;
            case 'layanan':
                if (empty($data['service_id'])) {
                    return back()->withErrors(['template' => 'Pilih layanan terlebih dahulu.']);
                }
                $query->where('service_id', $data['service_id']);
                $keterangan = 'semua halaman layanan terpilih';
                break;
            default:
                $keterangan = 'SEMUA halaman';
        }

        $jumlah = (clone $query)->count();
        $query->update(['template_id' => $templateId]);

        RenderCache::bump();

        $nama = $templateId
            ? (string) Template::find($templateId)?->name
            : 'template aktif (bawaan)';

        return back()->with('status', "{$jumlah} halaman ({$keterangan}) kini memakai: {$nama}.");
    }

    public function publish(Page $page)
    {
        $page->update(['status' => Page::STATUS_PUBLISHED, 'published_at' => now()]);
        SitemapService::flushCache();

        return back()->with('status', "Halaman /{$page->path} dipublikasikan.");
    }

    public function unpublish(Page $page)
    {
        $page->update(['status' => Page::STATUS_DRAFT, 'published_at' => null]);
        SitemapService::flushCache();

        return back()->with('status', "Halaman /{$page->path} dijadikan draft.");
    }

    public function publishQueue(Request $request)
    {
        $batchId = $request->input('import_batch_id') ?: null;
        $batchId = $batchId ? (int) $batchId : null;

        $targetCount = Page::query()
            ->draft()
            ->when($batchId, fn ($query) => $query->where('import_batch_id', $batchId))
            ->count();

        PublishControl::start($batchId, $targetCount);
        PublishPagesJob::dispatch($batchId);

        return back()->with('status', $targetCount > 0
            ? 'Publish queue dimulai di background.'
            : 'Tidak ada draft untuk dipublish. Sistem tetap akan mengecek dan menutup queue.');
    }

    public function pausePublish()
    {
        PublishControl::pause();

        return back()->with('status', 'Publish queue di-pause.');
    }

    public function resumePublish()
    {
        PublishControl::resume();
        PublishPagesJob::dispatch(PublishControl::batchId());

        return back()->with('status', 'Publish queue dilanjutkan.');
    }

    public function publishStatus()
    {
        $meta = PublishControl::meta();
        $batchId = $meta['batch_id'] ?? null;

        $remaining = Page::query()
            ->draft()
            ->when($batchId, fn ($query) => $query->where('import_batch_id', $batchId))
            ->count();

        $target = (int) ($meta['target_count'] ?? 0);
        $completed = max(0, min($target, $target - $remaining));
        $percent = $target > 0 ? (int) floor(($completed / $target) * 100) : 100;

        return response()->json([
            'state' => PublishControl::state(),
            'target_count' => $target,
            'completed_count' => $completed,
            'remaining_count' => $remaining,
            'percent' => $percent,
            'run_id' => $meta['run_id'] ?? null,
            'batch_id' => $batchId,
            'started_at' => $meta['started_at'] ?? null,
            'completed_at' => $meta['completed_at'] ?? null,
            'message' => $meta['message'] ?? null,
        ]);
    }
}
