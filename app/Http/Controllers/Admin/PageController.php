<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\PublishPagesJob;
use App\Models\ImportBatch;
use App\Models\Page;
use App\Services\SitemapService;
use App\Support\PublishControl;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $q = $request->query('q');

        $pages = Page::query()
            ->with(['service:id,name', 'city:id,name'])
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
        ]);
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

    /**
     * Mulai publish queue (semua draft, atau per import batch).
     */
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
