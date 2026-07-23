<?php

namespace App\Jobs;

use App\Models\ContentBlock;
use App\Models\Faq;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Services\Ai\AiContentGenerator;
use App\Services\Ai\AiException;
use App\Services\ContentHealthService;
use App\Services\ContentRepository;
use App\Support\AiFillProgress;
use App\Support\RegionDataset;
use App\Support\RenderCache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * "MULAI CEPAT": satu keyword → semua terisi sampai hijau → halaman siap review.
 *
 * Merangkai seluruh langkah yang biasanya manual:
 *   1. Isi variasi konten sampai memenuhi target (AI + otak MD)
 *   2. Isi FAQ sampai target
 *   3. Susun baris lokasi dari dataset wilayah RESMI (bukan AI — anti halusinasi)
 *   4. Buat batch import + baris, lalu jalankan generate halaman (status DRAFT)
 *
 * SENGAJA BERHENTI SEBELUM PUBLISH. Menerbitkan halaman adalah keputusan
 * berdampak besar dan sulit dibatalkan; owner harus memeriksa sampel dulu.
 */
class QuickStartJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3000;
    public int $tries = 1;

    /**
     * @param array<string,string> $context brand, business, keywords, tone
     * @param array<int,string> $layanan daftar nama layanan (cross-join)
     * @param string $cityKode kode wilayah level-2 (mis. '32.76'); kosong = lewati pembuatan halaman
     * @param array<string,string> $extra kolom data lokal seragam (harga, jam_operasional, …)
     */
    public function __construct(
        public array $context,
        public array $layanan,
        public string $cityKode = '',
        public string $level = 'kelurahan',
        public array $extra = [],
        public bool $isiTestimoni = true,
    ) {
    }

    public function handle(AiContentGenerator $generator, ContentHealthService $health): void
    {
        $p = [
            'status' => AiFillProgress::RUNNING,
            'message' => 'Memulai…',
            'current' => null,
            'sections' => [],
            'faq' => ['added' => 0, 'target' => 0],
            'tokens' => 0,
            'calls' => 0,
            'halaman' => 0,
            'started_at' => now()->toDateTimeString(),
            'finished_at' => null,
        ];
        AiFillProgress::put($p);

        try {
            // ---------- TAHAP 1: variasi konten ----------
            $counts = ContentBlock::query()->where('is_active', true)
                ->selectRaw('section, count(*) as total')->groupBy('section')
                ->pluck('total', 'section');

            foreach (ContentHealthService::TARGETS as $section => $target) {
                if ($section === 'testimoni' && ! $this->isiTestimoni) {
                    continue;
                }

                $need = max(0, $target - (int) ($counts[$section] ?? 0));
                $p['current'] = $section;
                $p['message'] = "Menulis bagian: {$section}" . ($need > 0 ? " (butuh {$need})" : ' (sudah cukup)');
                AiFillProgress::put($p);

                $r = $need > 0
                    ? $generator->fillSection($section, $this->context, $need)
                    : ['added' => 0, 'tokens' => 0, 'calls' => 0];

                $p['sections'][$section] = ['added' => $r['added'], 'need' => $need];
                $p['tokens'] += $r['tokens'];
                $p['calls'] += $r['calls'];
                AiFillProgress::put($p);
            }

            // ---------- TAHAP 2: FAQ ----------
            $faqNeed = max(0, ContentHealthService::FAQ_TARGET - (int) Faq::where('is_active', true)->count());
            $p['current'] = 'faq';
            $p['message'] = "Menulis FAQ" . ($faqNeed > 0 ? " (butuh {$faqNeed})" : ' (sudah cukup)');
            AiFillProgress::put($p);

            $fr = $faqNeed > 0
                ? $generator->fillFaqs($this->context, $faqNeed)
                : ['added' => 0, 'tokens' => 0, 'calls' => 0];
            $p['faq'] = ['added' => $fr['added'], 'target' => ContentHealthService::FAQ_TARGET];
            $p['tokens'] += $fr['tokens'];
            $p['calls'] += $fr['calls'];

            ContentRepository::flushCache();
            RenderCache::bump();
            AiFillProgress::put($p);

            // ---------- TAHAP 3 & 4: lokasi + halaman ----------
            if ($this->cityKode !== '' && $this->layanan !== []) {
                $p['current'] = 'halaman';
                $p['message'] = 'Menyusun lokasi dari data wilayah resmi…';
                AiFillProgress::put($p);

                $lokasi = RegionDataset::rows($this->cityKode, $this->level);

                if ($lokasi !== []) {
                    $batch = ImportBatch::create([
                        'original_filename' => 'mulai-cepat-' . now()->format('Ymd-His') . '.csv',
                        'stored_path' => null,
                        'layanan_list' => implode("\n", $this->layanan),
                        'status' => ImportBatch::STATUS_PROCESSING,
                        'total_rows' => 0,
                    ]);

                    $extraJson = $this->extra !== []
                        ? json_encode($this->extra, JSON_UNESCAPED_UNICODE)
                        : null;

                    $buffer = [];
                    $total = 0;
                    foreach ($lokasi as $loc) {
                        foreach ($this->layanan as $svc) {
                            $buffer[] = [
                                'import_batch_id' => $batch->id,
                                'layanan' => $svc,
                                'kota' => $loc['kota'],
                                'kecamatan' => $loc['kecamatan'],
                                'kelurahan' => $loc['kelurahan'],
                                'extra' => $extraJson,
                                'status' => ImportRow::STATUS_PENDING,
                            ];
                            $total++;

                            if (count($buffer) >= 1000) {
                                ImportRow::insert($buffer);
                                $buffer = [];
                            }
                        }
                    }
                    if ($buffer) {
                        ImportRow::insert($buffer);
                    }

                    $batch->update(['total_rows' => $total]);

                    $p['halaman'] = $total;
                    $p['message'] = "Membuat {$total} halaman (draft)…";
                    AiFillProgress::put($p);

                    GeneratePagesJob::dispatch($batch->id);
                }
            }

            // ---------- Selesai ----------
            $final = $health->health();
            $p['status'] = AiFillProgress::DONE;
            $p['current'] = null;
            $p['finished_at'] = now()->toDateTimeString();
            $p['message'] = $final['all_ok']
                ? '✅ Semua indikator hijau.' . ($p['halaman'] ? " {$p['halaman']} halaman dibuat sebagai DRAFT — periksa sampel lalu publish bertahap." : '')
                : 'Selesai, tapi sebagian target belum tercapai. Jalankan sekali lagi.';
            AiFillProgress::put($p);
        } catch (AiException $e) {
            $this->gagal($p, 'AI: ' . $e->getMessage());
        } catch (\Throwable $e) {
            $this->gagal($p, 'Kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * @param array<string,mixed> $p
     */
    private function gagal(array $p, string $pesan): void
    {
        ContentRepository::flushCache();
        RenderCache::bump();

        $p['status'] = AiFillProgress::ERROR;
        $p['finished_at'] = now()->toDateTimeString();
        $p['message'] = $pesan;
        AiFillProgress::put($p);
    }
}
