<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadClick;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Statistik klik tombol WhatsApp.
 *
 * PENTING — batas ukur: yang tercatat di sini adalah KLIK tombol, bukan chat
 * yang benar-benar terkirim ke CS. Setelah tombol diklik, percakapan terjadi di
 * aplikasi WhatsApp yang tidak bisa diakses server. Sebagian orang membatalkan
 * sebelum mengirim pesan, jadi jumlah chat sesungguhnya biasanya LEBIH KECIL
 * dari angka di panel ini.
 *
 * Untuk data chat sungguhan diperlukan WhatsApp Business Cloud API (Meta) —
 * lihat docs/LEAD-WHATSAPP.md.
 */
class LeadController extends Controller
{
    public function index()
    {
        if (! Schema::hasTable('lead_clicks')) {
            return view('admin.leads.index', ['tersedia' => false]);
        }

        $now = now();

        $hariIni = (int) LeadClick::whereDate('created_at', $now->toDateString())->count();
        $kemarin = (int) LeadClick::whereDate('created_at', $now->copy()->subDay()->toDateString())->count();
        $mingguIni = (int) LeadClick::where('created_at', '>=', $now->copy()->startOfWeek())->count();
        $bulanIni = (int) LeadClick::where('created_at', '>=', $now->copy()->startOfMonth())->count();
        $bulanLalu = (int) LeadClick::whereBetween('created_at', [
            $now->copy()->subMonthNoOverflow()->startOfMonth(),
            $now->copy()->subMonthNoOverflow()->endOfMonth(),
        ])->count();
        $tahunIni = (int) LeadClick::where('created_at', '>=', $now->copy()->startOfYear())->count();
        $total = (int) LeadClick::count();

        // Tren 30 hari terakhir (untuk grafik batang sederhana).
        $tren = LeadClick::where('created_at', '>=', $now->copy()->subDays(29)->startOfDay())
            ->select(DB::raw('DATE(created_at) as tgl'), DB::raw('count(*) as jml'))
            ->groupBy('tgl')
            ->pluck('jml', 'tgl')
            ->toArray();

        $hari = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = $now->copy()->subDays($i)->toDateString();
            $hari[$d] = (int) ($tren[$d] ?? 0);
        }

        // Sebaran per bulan tahun ini.
        $perBulan = LeadClick::where('created_at', '>=', $now->copy()->startOfYear())
            ->select(DB::raw('MONTH(created_at) as bln'), DB::raw('count(*) as jml'))
            ->groupBy('bln')
            ->pluck('jml', 'bln')
            ->toArray();

        return view('admin.leads.index', [
            'tersedia' => true,
            'hariIni' => $hariIni,
            'kemarin' => $kemarin,
            'mingguIni' => $mingguIni,
            'bulanIni' => $bulanIni,
            'bulanLalu' => $bulanLalu,
            'tahunIni' => $tahunIni,
            'total' => $total,
            'hari' => $hari,
            'perBulan' => $perBulan,
            'topHalaman' => $this->top('page_path', 15),
            'topKota' => $this->top('city', 10),
            'topLayanan' => $this->top('service', 10),
            'perSumber' => LeadClick::select('source', DB::raw('count(*) as jml'))
                ->groupBy('source')->pluck('jml', 'source')->toArray(),
            // Sebaran per nomor CS (rotator) — terlihat beban tiap CS & apakah
            // rotatornya membagi merata.
            'perNomor' => Schema::hasColumn('lead_clicks', 'wa_number')
                ? LeadClick::whereNotNull('wa_number')
                    ->select('wa_number',
                        DB::raw('count(*) as jml'),
                        DB::raw('sum(case when opened_at is not null then 1 else 0 end) as terbuka'))
                    ->groupBy('wa_number')->orderByDesc('jml')->get()->toArray()
                : [],
            'totalTerbuka' => Schema::hasColumn('lead_clicks', 'opened_at')
                ? (int) LeadClick::whereNotNull('opened_at')->count()
                : 0,
            'pertamaKali' => LeadClick::min('created_at'),
        ]);
    }

    /**
     * @return array<string,int>
     */
    private function top(string $kolom, int $limit): array
    {
        return LeadClick::whereNotNull($kolom)
            ->where($kolom, '!=', '')
            ->select($kolom, DB::raw('count(*) as jml'))
            ->groupBy($kolom)
            ->orderByDesc('jml')
            ->limit($limit)
            ->pluck('jml', $kolom)
            ->toArray();
    }
}
