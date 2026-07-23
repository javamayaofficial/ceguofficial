<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Ai\AiClientFactory;
use App\Services\Ai\KeywordGenerator;
use Illuminate\Http\Request;

/**
 * Alat bantu: hasilkan daftar keyword longtail (dimensi "layanan") via AI, siap
 * ditempel ke "Daftar Layanan" pada form Import (cross-join × lokasi resmi).
 */
class KeywordController extends Controller
{
    public function index()
    {
        return view('admin.keywords.index', [
            'keywords' => null,
            'configured' => AiClientFactory::isConfigured(),
        ]);
    }

    public function generate(Request $request, KeywordGenerator $generator)
    {
        if (! AiClientFactory::isConfigured()) {
            return back()->withErrors(['ai' => 'Kunci API AI belum diatur. Isi AI_* di .env lalu jalankan php artisan config:clear.']);
        }

        $data = $request->validate([
            'business' => ['required', 'string', 'max:200'],
            'seeds' => ['nullable', 'string', 'max:1000'],
            'count' => ['nullable', 'integer', 'min:10', 'max:400'],
        ]);

        try {
            $keywords = $generator->generate([
                'business' => $data['business'],
                'seeds' => $data['seeds'] ?? '',
            ], (int) ($data['count'] ?? 100));
        } catch (\Throwable $e) {
            return back()->withErrors(['ai' => 'Gagal generate: ' . $e->getMessage()])->withInput();
        }

        return view('admin.keywords.index', [
            'keywords' => $keywords,
            'configured' => true,
            'business' => $data['business'],
        ]);
    }
}
