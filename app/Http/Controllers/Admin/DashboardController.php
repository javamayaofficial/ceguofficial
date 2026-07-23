<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Models\LeadClick;
use App\Models\Page;
use App\Services\ContentHealthService;
use App\Services\SearchConsole\SearchConsoleService;
use App\Support\PublishControl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Dashboard Generate (RFP): total, draft, published, sedang diproses, selesai, gagal.
     */
    public function index()
    {
        $statusCounts = Page::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [
            'total' => (int) $statusCounts->sum(),
            'draft' => (int) ($statusCounts['draft'] ?? 0),
            'published' => (int) ($statusCounts['published'] ?? 0),
        ];

        $batchAgg = ImportBatch::query()
            ->selectRaw('COALESCE(SUM(total_rows),0) total, COALESCE(SUM(processed_rows),0) processed, COALESCE(SUM(failed_count),0) failed')
            ->first();

        $generate = [
            'total_rows' => (int) ($batchAgg->total ?? 0),
            'processing' => (int) max(0, ($batchAgg->total ?? 0) - ($batchAgg->processed ?? 0)),
            'done' => (int) ($batchAgg->processed ?? 0),
            'failed' => (int) ($batchAgg->failed ?? 0),
        ];

        $batches = ImportBatch::latest()->limit(5)->get();
        $publishState = PublishControl::state();

        // Naik level: kesehatan stok konten + checklist onboarding otomatis.
        $healthService = new ContentHealthService();
        $health = $healthService->health();
        $onboarding = $healthService->onboarding($health);

        // Pelacakan lead (klik WhatsApp) 30 hari terakhir. Aman bila tabel belum
        // dimigrasi (fitur baru): tampil kosong, tidak error.
        $leads = ['total' => 0, 'today' => 0, 'top' => collect()];
        if (Schema::hasTable('lead_clicks')) {
            $since = now()->subDays(30);
            $leads['total'] = (int) LeadClick::where('created_at', '>=', $since)->count();
            $leads['today'] = (int) LeadClick::where('created_at', '>=', now()->startOfDay())->count();
            $leads['top'] = LeadClick::where('created_at', '>=', $since)
                ->select('page_path', DB::raw('count(*) as total'))
                ->groupBy('page_path')
                ->orderByDesc('total')
                ->limit(10)
                ->get();
        }

        // Google Search Console (opsional). Di-cache 6 jam; aman bila belum
        // dikonfigurasi atau API error → widget sekadar tidak tampil.
        $gsc = null;
        if (SearchConsoleService::isConfigured()) {
            $gsc = cache()->remember('dashboard.gsc.summary', 21600, function () {
                try {
                    return app(SearchConsoleService::class)->summary(28);
                } catch (\Throwable $e) {
                    return ['error' => $e->getMessage()];
                }
            });
        }

        return view('admin.dashboard', compact('stats', 'generate', 'batches', 'publishState', 'health', 'onboarding', 'leads', 'gsc'));
    }
}
