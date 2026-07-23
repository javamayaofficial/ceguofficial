<?php

namespace App\Http\Controllers;

use App\Models\LeadClick;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Endpoint pelacakan klik WhatsApp (dipanggil via navigator.sendBeacon dari
 * halaman publik). Stateless, tanpa auth, di-throttle. Tidak menyimpan data
 * pribadi — hanya konteks halaman.
 */
class LeadTrackController extends Controller
{
    public function store(Request $request)
    {
        $path = Str::limit(trim((string) $request->input('path', '')), 500, '');
        $service = $request->input('service');
        $city = $request->input('city');
        $source = $request->input('source');

        // Batasi panjang & abaikan payload sampah.
        if ($path === '') {
            return response()->noContent();
        }

        // Konfirmasi "WhatsApp benar-benar terbuka": browser mengirim token yang
        // sama saat halaman berpindah ke latar belakang sesaat setelah klik.
        if ($request->filled('confirm')) {
            LeadClick::where('token', Str::limit((string) $request->input('confirm'), 40, ''))
                ->whereNull('opened_at')
                ->latest('id')
                ->limit(1)
                ->update(['opened_at' => now()]);

            return response()->noContent();
        }

        LeadClick::create([
            'page_path' => $path,
            'service' => $service ? Str::limit((string) $service, 185, '') : null,
            'city' => $city ? Str::limit((string) $city, 185, '') : null,
            'wa_number' => preg_replace('/\D/', '', (string) $request->input('wa_number', '')) ?: null,
            'source' => in_array($source, ['float', 'nav', 'inline'], true) ? $source : null,
            'token' => Str::limit((string) $request->input('token', ''), 40, '') ?: null,
            'created_at' => now(),
        ]);

        return response()->noContent();
    }
}
