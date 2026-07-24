<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateContentBlocksJob;
use App\Models\Setting;
use App\Services\Ai\AiClientFactory;
use App\Services\Ai\SeoAssistant;
use App\Services\Ai\SiteAuditService;
use App\Services\Ai\KnowledgeBase;
use App\Services\IndexNowService;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Asisten SEO di panel admin: diskusi + laporan + eksekusi aksi terbatas.
 *
 * Aksi dijalankan HANYA dari whitelist SeoAssistant::ACTIONS dan hanya setelah
 * owner menekan tombol — AI tidak pernah mengeksekusi sendiri.
 */
class AssistantController extends Controller
{
    public function index(SiteAuditService $audit)
    {
        return view('admin.assistant.index', [
            'configured' => AiClientFactory::isConfigured(),
            'audit' => $audit->fullAudit(),
            'knowledge' => (new KnowledgeBase())->list(),
            'catatan' => (new KnowledgeBase())->catatan(),
        ]);
    }

    public function simpanCatatan(Request $request)
    {
        $data = $request->validate([
            'catatan' => ['nullable', 'string', 'max:20000'],
        ]);

        (new KnowledgeBase())->simpanCatatan((string) ($data['catatan'] ?? ''));

        return back()->with('status', 'Catatan lapangan disimpan. Asisten akan memakainya mulai percakapan berikutnya.');
    }

    /**
     * Tanya-jawab (dipanggil via fetch dari halaman asisten).
     */
    public function ask(Request $request, SeoAssistant $assistant)
    {
        if (! AiClientFactory::isConfigured()) {
            return response()->json([
                'jawaban' => 'Kunci API AI belum diatur. Isi AI_DRIVER, AI_API_KEY, dan AI_MODEL di file .env lalu jalankan: php artisan config:clear.',
                'aksi' => [],
            ]);
        }

        $data = $request->validate([
            'pertanyaan' => ['required', 'string', 'max:4000'],
            'riwayat' => ['nullable', 'array', 'max:20'],
            'riwayat.*.role' => ['nullable', 'string', 'in:user,assistant'],
            'riwayat.*.content' => ['nullable', 'string', 'max:4000'],
        ]);

        try {
            $res = $assistant->ask($data['pertanyaan'], $data['riwayat'] ?? []);
        } catch (\Throwable $e) {
            return response()->json([
                'jawaban' => 'Maaf, terjadi kendala saat menghubungi AI: ' . $e->getMessage(),
                'aksi' => [],
            ]);
        }

        return response()->json($res);
    }

    /**
     * Laporan kesehatan lengkap (tombol "Buat Laporan").
     */
    public function report(SeoAssistant $assistant)
    {
        if (! AiClientFactory::isConfigured()) {
            return response()->json(['jawaban' => 'Kunci API AI belum diatur.', 'aksi' => []]);
        }

        try {
            return response()->json($assistant->report());
        } catch (\Throwable $e) {
            return response()->json(['jawaban' => 'Gagal membuat laporan: ' . $e->getMessage(), 'aksi' => []]);
        }
    }

    /**
     * Jalankan aksi yang diusulkan asisten (setelah dikonfirmasi owner).
     */
    public function execute(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'string'],
        ]);

        $id = $data['id'];
        if (! isset(SeoAssistant::ACTIONS[$id])) {
            return response()->json(['ok' => false, 'pesan' => 'Aksi tidak dikenali.'], 422);
        }

        // Aksi navigasi: cukup kembalikan URL tujuan.
        $redirects = [
            'buka_pengaturan' => 'admin.settings.edit',
            'buka_import' => 'admin.imports.index',
            'buka_publish' => 'admin.dashboard',
        ];
        if (isset($redirects[$id])) {
            return response()->json(['ok' => true, 'redirect' => route($redirects[$id])]);
        }

        try {
            return response()->json($this->runAction($id));
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'pesan' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function runAction(string $id): array
    {
        switch ($id) {
            case 'isi_konten':
                GenerateContentBlocksJob::dispatch([
                    'brand' => (string) Setting::get('brand_name', ''),
                    'business' => (string) Setting::get('tagline', '') ?: 'jasa lokal',
                    'keywords' => '',
                    'tone' => '',
                ], true, 1.0);

                return [
                    'ok' => true,
                    'pesan' => 'Proses pengisian konten dimulai di antrian. Pastikan worker (queue:work) berjalan. Pantau progresnya di menu Variasi Konten.',
                ];

            case 'generate_keyword':
                return [
                    'ok' => true,
                    'redirect' => route('admin.keywords.index'),
                    'pesan' => 'Membuka generator keyword…',
                ];

            case 'warm_cache':
                Artisan::queue('pages:warm', ['--limit' => 1000]);

                return ['ok' => true, 'pesan' => 'Pemanasan cache 1.000 halaman dijadwalkan di antrian.'];

            case 'submit_indexnow':
                if (! IndexNowService::isEnabled()) {
                    return ['ok' => false, 'pesan' => 'IndexNow belum aktif. Isi INDEXNOW_KEY di .env terlebih dahulu.'];
                }
                if (Page::published()->count() === 0) {
                    return ['ok' => false, 'pesan' => 'Belum ada halaman published untuk dikirim.'];
                }
                Artisan::queue('indexnow:submit', ['--limit' => 10000]);

                return ['ok' => true, 'pesan' => 'Pengiriman URL ke IndexNow dijadwalkan di antrian.'];

            default:
                return ['ok' => false, 'pesan' => 'Aksi belum tersedia.'];
        }
    }
}
