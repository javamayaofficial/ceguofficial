<?php

namespace App\Http\Controllers;

use App\Models\LeadClick;
use App\Models\Page;
use App\Models\PageIndexStatus;
use App\Models\Setting;
use App\Services\ContentHealthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class StatusController extends Controller
{
    public function show(Request $request)
    {
        $token = trim((string) config('services.status_token', ''));

        abort_if($token === '', 404);
        abort_unless(hash_equals($token, (string) $request->query('token', '')), 404);

        $health = (new ContentHealthService())->health();

        $lead30 = Schema::hasTable('lead_clicks')
            ? (int) LeadClick::where('created_at', '>=', now()->subDays(30))->count()
            : 0;

        $terindeks = Schema::hasTable('page_index_statuses')
            ? PageIndexStatus::all()->filter(fn ($s) => $s->isIndexed())->count()
            : 0;

        return response()->json([
            'situs' => url('/'),
            'brand' => (string) Setting::get('brand_name', ''),
            'waktu' => now()->toDateTimeString(),
            'halaman' => [
                'total' => (int) Page::count(),
                'published' => (int) Page::published()->count(),
                'draft' => (int) Page::draft()->count(),
                'terindeks' => $terindeks,
            ],
            'konten' => [
                'skor' => (int) ($health['score'] ?? 0),
                'semua_hijau' => (bool) ($health['all_ok'] ?? false),
            ],
            'lead' => [
                'klik_30_hari' => $lead30,
                'klik_hari_ini' => Schema::hasTable('lead_clicks')
                    ? (int) LeadClick::whereDate('created_at', now()->toDateString())->count()
                    : 0,
            ],
            'peringatan' => $this->peringatan($health),
        ]);
    }

    private function peringatan(array $health): array
    {
        $peringatan = [];

        if (trim((string) Setting::get('whatsapp_number', '')) === '') {
            $peringatan[] = 'Nomor WhatsApp belum diisi';
        }
        if (! ($health['all_ok'] ?? false)) {
            $peringatan[] = 'Stok konten belum lengkap';
        }
        if ((int) Page::published()->count() === 0 && (int) Page::count() > 0) {
            $peringatan[] = 'Ada halaman draft, belum ada yang dipublish';
        }
        if (str_contains((string) Setting::get('default_robots', 'index,follow'), 'noindex')) {
            $peringatan[] = 'Setelan robots masih noindex';
        }

        return $peringatan;
    }
}
