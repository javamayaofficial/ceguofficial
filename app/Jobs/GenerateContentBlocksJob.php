<?php

namespace App\Jobs;

use App\Models\ContentBlock;
use App\Models\Faq;
use App\Services\Ai\AiContentGenerator;
use App\Services\Ai\AiException;
use App\Services\ContentHealthService;
use App\Services\ContentRepository;
use App\Support\AiFillProgress;
use App\Support\RenderCache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * "Isi Otomatis dengan AI": generate variasi konten + FAQ sampai semua
 * indikator kesehatan HIJAU (target di ContentHealthService).
 *
 * Berjalan di antrian (queue) agar tidak membebani request web. Progres ditulis
 * ke AiFillProgress sehingga panel admin bisa memantau tanpa tabel baru.
 *
 * $multiplier: kalikan target untuk pool lebih kaya (mis. 1.5 = isi sampai 150%
 * target). Default 1.0 = cukup untuk hijau.
 */
class GenerateContentBlocksJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;
    public int $tries = 1;

    /**
     * @param array<string,string> $context brand, business, keywords, tone
     */
    public function __construct(
        public array $context,
        public bool $includeFaq = true,
        public float $multiplier = 1.0,
    ) {
    }

    public function handle(AiContentGenerator $generator, ContentHealthService $health): void
    {
        $mult = max(1.0, min(3.0, $this->multiplier));

        // Hitung kekurangan awal per section.
        $counts = ContentBlock::query()
            ->where('is_active', true)
            ->selectRaw('section, count(*) as total')
            ->groupBy('section')
            ->pluck('total', 'section');

        $progress = [
            'status' => AiFillProgress::RUNNING,
            'message' => 'Memulai pengisian otomatis… (' . $generator->label() . ')',
            'current' => null,
            'sections' => [],
            'faq' => ['added' => 0, 'target' => 0],
            'tokens' => 0,
            'calls' => 0,
            'started_at' => now()->toDateTimeString(),
            'finished_at' => null,
        ];
        AiFillProgress::put($progress);

        try {
            foreach (ContentHealthService::TARGETS as $section => $baseTarget) {
                $target = (int) ceil($baseTarget * $mult);
                $current = (int) ($counts[$section] ?? 0);
                $need = max(0, $target - $current);

                $progress['current'] = $section;
                $progress['message'] = "Mengisi bagian: {$section} (butuh {$need} lagi)…";
                AiFillProgress::put($progress);

                $result = $need > 0
                    ? $generator->fillSection($section, $this->context, $need)
                    : ['added' => 0, 'tokens' => 0, 'calls' => 0];

                $progress['sections'][$section] = [
                    'added' => $result['added'],
                    'need' => $need,
                    'target' => $target,
                    'final' => $current + $result['added'],
                ];
                $progress['tokens'] += $result['tokens'];
                $progress['calls'] += $result['calls'];
                AiFillProgress::put($progress);
            }

            if ($this->includeFaq) {
                $faqTarget = (int) ceil(ContentHealthService::FAQ_TARGET * $mult);
                $faqCurrent = (int) Faq::query()->where('is_active', true)->count();
                $faqNeed = max(0, $faqTarget - $faqCurrent);

                $progress['current'] = 'faq';
                $progress['message'] = "Mengisi FAQ (butuh {$faqNeed} lagi)…";
                AiFillProgress::put($progress);

                $faqRes = $faqNeed > 0
                    ? $generator->fillFaqs($this->context, $faqNeed)
                    : ['added' => 0, 'tokens' => 0, 'calls' => 0];

                $progress['faq'] = ['added' => $faqRes['added'], 'target' => $faqTarget];
                $progress['tokens'] += $faqRes['tokens'];
                $progress['calls'] += $faqRes['calls'];
            }

            // Segarkan cache pool & versi render agar halaman langsung memakai variasi baru.
            ContentRepository::flushCache();
            RenderCache::bump();

            $finalHealth = $health->health();
            $progress['status'] = AiFillProgress::DONE;
            $progress['current'] = null;
            $progress['finished_at'] = now()->toDateTimeString();
            $progress['message'] = $finalHealth['all_ok']
                ? '✅ Selesai — semua indikator sudah hijau.'
                : 'Selesai, namun sebagian target belum tercapai. Coba jalankan sekali lagi.';
            AiFillProgress::put($progress);
        } catch (AiException $e) {
            $this->fail($progress, 'AI: ' . $e->getMessage());
        } catch (\Throwable $e) {
            $this->fail($progress, 'Kesalahan tak terduga: ' . $e->getMessage());
        }
    }

    /**
     * @param array<string,mixed> $progress
     */
    private function fail(array $progress, string $message): void
    {
        // Simpan apa pun yang sudah berhasil sebelum error.
        ContentRepository::flushCache();
        RenderCache::bump();

        $progress['status'] = AiFillProgress::ERROR;
        $progress['finished_at'] = now()->toDateTimeString();
        $progress['message'] = $message;
        AiFillProgress::put($progress);
    }
}
