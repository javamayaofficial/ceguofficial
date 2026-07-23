<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\InspectPagesJob;
use App\Jobs\SyncIndexFromAnalyticsJob;
use App\Models\Page;
use App\Models\PageIndexStatus;
use App\Services\SearchConsole\SearchConsoleService;
use App\Services\SearchConsole\UrlInspectionService;
use Illuminate\Http\Request;

/**
 * Panel Indexing: berapa halaman sudah terindeks Google, berapa yang belum
 * (beserta alasannya), dan berapa yang benar-benar muncul di peringkat.
 *
 * Juga menyediakan pemeriksaan URL manual/massal sesuai kuota Google.
 */
class IndexingController extends Controller
{
    public function index()
    {
        $configured = UrlInspectionService::isConfigured();

        $ringkasan = $configured
            ? UrlInspectionService::summary()
            : ['published' => (int) Page::published()->count(), 'checked' => 0, 'belum_dicek' => 0,
               'terindeks' => 0, 'belum_terindeks' => 0, 'alasan' => [],
               'kuota_terpakai' => 0, 'kuota_sisa' => 0];

        // Data peringkat (di-cache 6 jam — hemat kuota API).
        $ranking = null;
        if ($configured) {
            $ranking = cache()->remember('gsc:ranking:breakdown', 21600, function () {
                try {
                    return app(SearchConsoleService::class)->rankingBreakdown(28);
                } catch (\Throwable $e) {
                    return ['error' => $e->getMessage()];
                }
            });
        }

        // ESTIMASI TERINDEKS dari data pencarian (tanpa kuota per-URL) —
        // cara paling efisien memantau situs besar. Di-cache 6 jam.
        $estimasi = null;
        if ($configured) {
            $estimasi = cache()->remember('gsc:indexed:analytics', 21600, function () {
                try {
                    return app(SearchConsoleService::class)->indexedByAnalytics(90);
                } catch (\Throwable $e) {
                    return ['error' => $e->getMessage()];
                }
            });
        }

        // DAFTAR KERJA "BELUM TERINDEKS":
        // halaman published yang BELUM terbukti terindeks — yaitu yang tidak
        // punya status, atau statusnya bukan "terindeks". Diurutkan agar yang
        // belum pernah diminta indexing muncul lebih dulu.
        $belum = Page::published()
            ->leftJoin('page_index_statuses as s', 's.page_id', '=', 'pages.id')
            ->where(function ($q) {
                $q->whereNull('s.id')
                    ->orWhere(function ($q2) {
                        foreach (PageIndexStatus::INDEXED_HINTS as $hint) {
                            $q2->where('s.coverage_state', 'not like', '%' . $hint . '%');
                        }
                    });
            })
            ->orderByRaw('s.requested_at IS NOT NULL, s.requested_at ASC, pages.id ASC')
            ->select('pages.id', 'pages.path', 's.coverage_state', 's.requested_at', 's.checked_at')
            ->paginate(50)
            ->withQueryString();

        return view('admin.indexing.index', [
            'configured' => $configured,
            'ringkasan' => $ringkasan,
            'ranking' => $ranking,
            'belum' => $belum,
            'estimasi' => $estimasi,
            'progress' => InspectPagesJob::progress(),
            'sync' => SyncIndexFromAnalyticsJob::state(),
            'gscBaseUrl' => $this->gscInspectBase(),
        ]);
    }

    /** Mulai pemeriksaan massal (sesuai kuota). */
    public function inspect(Request $request)
    {
        if (! UrlInspectionService::isConfigured()) {
            return back()->withErrors(['gsc' => 'Search Console belum dikonfigurasi. Isi GSC_CREDENTIALS & GSC_SITE_URL di .env.']);
        }

        $data = $request->validate([
            'jumlah' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'sampling' => ['nullable', 'boolean'],
        ]);

        $sisa = UrlInspectionService::remainingToday();
        if ($sisa <= 0) {
            return back()->with('status', 'Kuota inspeksi harian Google (2.000) sudah habis. Coba lagi besok.');
        }

        $jumlah = min((int) ($data['jumlah'] ?? 100), $sisa);
        $sampling = $request->boolean('sampling');
        InspectPagesJob::dispatch($jumlah, 14, $sampling);

        $mode = $sampling ? 'sampel acak' : 'prioritas belum diperiksa';

        return back()->with('status', "Pemeriksaan {$jumlah} halaman ({$mode}) dimulai. Sisa kuota hari ini: {$sisa}.");
    }

    /** Periksa SATU halaman (tombol per baris). */
    public function inspectOne(Request $request, UrlInspectionService $inspector)
    {
        $data = $request->validate(['page_id' => ['required', 'integer']]);

        $page = Page::find($data['page_id']);
        if (! $page) {
            return response()->json(['ok' => false, 'pesan' => 'Halaman tidak ditemukan.'], 404);
        }

        try {
            $status = $inspector->inspect($page);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'pesan' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'verdict' => $status->verdict,
            'coverage' => $status->coverage_state,
            'terindeks' => $status->isIndexed(),
            'sisa_kuota' => UrlInspectionService::remainingToday(),
        ]);
    }

    /** Tandai halaman terindeks dari data pencarian — TANPA memakai kuota inspeksi. */
    public function sync()
    {
        if (! SearchConsoleService::isConfigured()) {
            return back()->withErrors(['gsc' => 'Search Console belum dikonfigurasi.']);
        }

        SyncIndexFromAnalyticsJob::dispatch(90);

        return back()->with('status', 'Sinkronisasi data pencarian dimulai. Ini tidak memakai kuota inspeksi.');
    }

    /** Catat bahwa owner sudah menekan "Request Indexing" di Search Console. */
    public function markRequested(Request $request)
    {
        $data = $request->validate(['page_id' => ['required', 'integer']]);

        $page = Page::find($data['page_id']);
        if (! $page) {
            return response()->json(['ok' => false], 404);
        }

        PageIndexStatus::updateOrCreate(
            ['page_id' => $page->id],
            ['requested_at' => now()],
        );

        return response()->json([
            'ok' => true,
            'diminta_hari_ini' => PageIndexStatus::whereDate('requested_at', now()->toDateString())->count(),
        ]);
    }

    public function progress()
    {
        return response()->json(InspectPagesJob::progress() + [
            'kuota_sisa' => UrlInspectionService::remainingToday(),
        ]);
    }

    /**
     * Basis URL alat URL Inspection di Search Console, untuk tombol
     * "Minta Indexing" manual (Google tidak menyediakan API untuk ini pada
     * halaman umum — lihat docs/INDEXING.md).
     */
    private function gscInspectBase(): string
    {
        $site = trim((string) config('services.gsc.site_url', '')) ?: url('/');

        return 'https://search.google.com/search-console/inspect?resource_id=' . rawurlencode($site) . '&id=';
    }
}
