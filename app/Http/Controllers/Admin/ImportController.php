<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GeneratePagesJob;
use App\Jobs\ImportCsvJob;
use App\Models\ImportBatch;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function index()
    {
        $batches = ImportBatch::latest()->paginate(15);

        return view('admin.imports.index', compact('batches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'csv' => ['required', 'file', 'extensions:csv,txt', 'max:51200'], // 50 MB
            'layanan_list' => ['nullable', 'string', 'max:5000'],
        ]);

        // Cross-join: daftar layanan (pisah baris/koma) untuk CSV lokasi
        // tanpa kolom layanan. Dinormalisasi & dide-duplikasi.
        $layananList = collect(preg_split('/[\r\n,]+/', (string) $request->input('layanan_list')))
            ->map(fn ($l) => trim($l))
            ->filter()
            ->unique()
            ->values();

        $file = $request->file('csv');
        $path = $file->store('imports', 'local');

        $batch = ImportBatch::create([
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'layanan_list' => $layananList->isNotEmpty() ? $layananList->implode("\n") : null,
            'status' => ImportBatch::STATUS_QUEUED,
        ]);

        ImportCsvJob::dispatch($batch->id);

        return redirect()->route('admin.imports.index')
            ->with('status', "CSV \"{$batch->original_filename}\" diterima. Generate berjalan di background.");
    }

    public function pause(ImportBatch $import)
    {
        $import->update(['status' => ImportBatch::STATUS_PAUSED]);

        return back()->with('status', 'Generate di-pause.');
    }

    public function resume(ImportBatch $import)
    {
        $import->update(['status' => ImportBatch::STATUS_PROCESSING]);
        GeneratePagesJob::dispatch($import->id);

        return back()->with('status', 'Generate dilanjutkan.');
    }

    /**
     * Status JSON untuk polling progress di dashboard.
     */
    public function status(ImportBatch $import)
    {
        return response()->json([
            'status' => $import->status,
            'total_rows' => $import->total_rows,
            'processed_rows' => $import->processed_rows,
            'generated_count' => $import->generated_count,
            'failed_count' => $import->failed_count,
            'percent' => $import->total_rows > 0
                ? round($import->processed_rows / $import->total_rows * 100, 1)
                : 0,
            // Baris error terbaru (maks 30) untuk konsol log realtime.
            'errors' => $import->error_log
                ? array_slice(array_filter(explode("\n", $import->error_log)), -30)
                : [],
        ]);
    }

    public function destroy(ImportBatch $import)
    {
        // Pages dari batch ini dibiarkan (sudah jadi konten). Hanya hapus catatan import.
        $import->delete();

        return back()->with('status', 'Catatan import dihapus.');
    }
}
