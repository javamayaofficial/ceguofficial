<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Models\ImportRow;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Support\CsvReader;
use Illuminate\Support\Facades\Storage;

/**
 * Membaca file CSV yang di-upload admin dan mem-stage barisnya ke `import_rows`.
 * Setelah selesai, men-dispatch GeneratePagesJob untuk mulai generate.
 *
 * Header CSV: layanan,kota,kecamatan,kelurahan
 */
class ImportCsvJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    /**
     * Nama kolom yang tidak boleh dipakai sebagai data lokal karena bentrok
     * dengan token bawaan mesin.
     */
    private const RESERVED_TOKENS = [
        'layanan', 'kota', 'kecamatan', 'kelurahan', 'brand', 'wa', 'wa_number',
        'hero', 'intro', 'about', 'cta', 'summary', 'pain_point', 'solusi', 'usp',
        'testimoni', 'pain_point_list', 'solusi_list', 'usp_list', 'testimoni_list',
        'faq', 'breadcrumb', 'internal_links', 'wa_button', 'hero_image',
        'hero_image_url', 'hero_alt', 'year', 'fakta_lokal',
    ];

    public function __construct(public int $batchId)
    {
    }

    public function handle(): void
    {
        $batch = ImportBatch::find($this->batchId);
        if (! $batch || ! $batch->stored_path) {
            return;
        }

        $batch->update(['status' => ImportBatch::STATUS_PROCESSING]);

        $fullPath = Storage::disk('local')->path($batch->stored_path);
        if (! is_file($fullPath)) {
            $batch->update(['status' => ImportBatch::STATUS_FAILED]);
            $batch->appendError("File CSV tidak ditemukan: {$batch->stored_path}");

            return;
        }

        $handle = fopen($fullPath, 'r');
        if ($handle === false) {
            $batch->update(['status' => ImportBatch::STATUS_FAILED]);
            $batch->appendError('Gagal membuka file CSV.');

            return;
        }

        $reader = new CsvReader($handle);
        $header = $reader->header();

        // MODE CROSS-JOIN: bila admin mengisi "Daftar Layanan" di form upload
        // dan CSV TIDAK punya kolom layanan, setiap baris lokasi digandakan
        // otomatis untuk semua layanan tersebut. Satu file lokasi Indonesia
        // → ratusan keyword, tanpa copy-paste di spreadsheet.
        $layananList = collect(preg_split('/\r?\n/', (string) $batch->layanan_list))
            ->map(fn ($l) => trim($l))->filter()->values()->all();
        $crossJoin = $layananList !== [] && ! in_array('layanan', $header, true);

        $required = $crossJoin
            ? ['kota', 'kecamatan', 'kelurahan']
            : ['layanan', 'kota', 'kecamatan', 'kelurahan'];

        if (array_diff($required, $header)) {
            fclose($handle);
            $batch->update(['status' => ImportBatch::STATUS_FAILED]);
            $batch->appendError($crossJoin
                ? 'Header CSV wajib (mode cross-join): kota,kecamatan,kelurahan'
                : 'Header CSV wajib: layanan,kota,kecamatan,kelurahan (atau isi Daftar Layanan di form dan hilangkan kolom layanan)');

            return;
        }

        $idx = array_flip($header);

        // FITUR DATA LOKAL: semua kolom di luar 4 kolom wajib otomatis menjadi
        // data lokal per halaman (token {{nama_kolom}}). Nama kolom dinormalisasi
        // agar valid sebagai token: huruf kecil, spasi/strip → underscore.
        $extraCols = [];
        foreach ($header as $i => $name) {
            if (in_array($name, $required, true)) {
                continue;
            }
            $key = preg_replace('/[^a-z0-9_]/', '_', str_replace([' ', '-'], '_', $name));
            $key = trim((string) $key, '_');
            if ($key !== '' && ! in_array($key, self::RESERVED_TOKENS, true)) {
                $extraCols[$i] = $key;
            }
        }

        $buffer = [];
        $total = 0;

        while (($data = $reader->row()) !== false) {
            if ($data === [null] || $data === false) {
                continue;
            }

            $extra = [];
            foreach ($extraCols as $i => $key) {
                $val = trim((string) ($data[$i] ?? ''));
                if ($val !== '') {
                    $extra[$key] = $val;
                }
            }

            $layanans = $crossJoin ? $layananList : [(string) ($data[$idx['layanan']] ?? '')];
            foreach ($layanans as $layanan) {
                $buffer[] = [
                    'import_batch_id' => $batch->id,
                    'layanan' => $layanan,
                    'kota' => $data[$idx['kota']] ?? '',
                    'kecamatan' => $data[$idx['kecamatan']] ?? '',
                    'kelurahan' => $data[$idx['kelurahan']] ?? '',
                    // insert() melewati cast Eloquent → encode manual.
                    'extra' => $extra !== [] ? json_encode($extra, JSON_UNESCAPED_UNICODE) : null,
                    'status' => ImportRow::STATUS_PENDING,
                ];
                $total++;
            }

            if (count($buffer) >= 1000) {
                ImportRow::insert($buffer);
                $buffer = [];
            }
        }
        if ($buffer) {
            ImportRow::insert($buffer);
        }
        fclose($handle);

        $batch->update(['total_rows' => $total]);

        // Mulai generate.
        GeneratePagesJob::dispatch($batch->id);
    }

}
