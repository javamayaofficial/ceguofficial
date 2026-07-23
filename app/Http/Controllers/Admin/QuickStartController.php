<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\QuickStartJob;
use App\Models\Setting;
use App\Services\Ai\AiClientFactory;
use App\Services\Ai\KnowledgeBase;
use App\Services\ContentHealthService;
use App\Support\AiFillProgress;
use App\Support\RegionDataset;
use Illuminate\Http\Request;

/**
 * "Mulai Cepat": satu form berisi kata kunci yang dibidik → seluruh stok konten,
 * FAQ, dan halaman terisi otomatis sampai indikator hijau.
 *
 * Berhenti di status DRAFT — publish tetap keputusan owner.
 */
class QuickStartController extends Controller
{
    public function index(ContentHealthService $health)
    {
        return view('admin.quickstart.index', [
            'configured' => AiClientFactory::isConfigured(),
            'health' => $health->health(),
            'provinces' => RegionDataset::provinces(),
            'cities' => RegionDataset::cities(),
            'knowledge' => (new KnowledgeBase())->list(),
            'running' => AiFillProgress::isRunning(),
            'brand' => (string) Setting::get('brand_name', ''),
            'waTerisi' => trim((string) Setting::get('whatsapp_number', '')) !== '',
        ]);
    }

    public function run(Request $request)
    {
        if (! AiClientFactory::isConfigured()) {
            return back()->withErrors(['ai' => 'Kunci API AI belum diatur. Isi AI_DRIVER, AI_API_KEY, dan AI_MODEL di .env lalu jalankan: php artisan config:clear']);
        }

        if (AiFillProgress::isRunning()) {
            return back()->with('status', 'Proses sebelumnya masih berjalan. Tunggu sampai selesai.');
        }

        $data = $request->validate([
            'keyword' => ['required', 'string', 'max:200'],
            'keyword_lain' => ['nullable', 'string', 'max:2000'],
            'business' => ['nullable', 'string', 'max:200'],
            'tone' => ['nullable', 'string', 'max:120'],
            'city_kode' => ['nullable', 'string', 'max:10'],
            'level' => ['nullable', 'string', 'in:kelurahan,kecamatan'],
            'harga' => ['nullable', 'string', 'max:100'],
            'jam_operasional' => ['nullable', 'string', 'max:100'],
            'jadwal' => ['nullable', 'string', 'max:100'],
            'isi_testimoni' => ['nullable', 'boolean'],
        ]);

        // Daftar layanan: keyword utama + tambahan (satu per baris/koma).
        $layanan = collect([$data['keyword']])
            ->merge(preg_split('/[\r\n,]+/', (string) ($data['keyword_lain'] ?? '')) ?: [])
            ->map(fn ($k) => trim((string) $k))
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Kolom data lokal seragam (hanya yang diisi).
        $extra = array_filter([
            'harga' => trim((string) ($data['harga'] ?? '')),
            'jam_operasional' => trim((string) ($data['jam_operasional'] ?? '')),
            'jadwal' => trim((string) ($data['jadwal'] ?? '')),
        ], fn ($v) => $v !== '');

        $context = [
            'brand' => (string) Setting::get('brand_name', ''),
            'business' => trim((string) ($data['business'] ?? '')) ?: $data['keyword'],
            'keywords' => implode(', ', $layanan),
            'tone' => (string) ($data['tone'] ?? ''),
        ];

        QuickStartJob::dispatch(
            $context,
            $layanan,
            (string) ($data['city_kode'] ?? ''),
            (string) ($data['level'] ?? 'kelurahan'),
            $extra,
            $request->boolean('isi_testimoni'),
        );

        return back()->with('status', 'Proses dimulai. Pastikan worker antrian (queue:work) berjalan — progres tampil di bawah.');
    }

    public function status()
    {
        return response()->json(AiFillProgress::get());
    }
}
