<?php

namespace App\Services;

use App\Models\ContentBlock;
use App\Models\Faq;

/**
 * Sumber pool konten (content blocks & FAQ) yang DI-CACHE.
 *
 * Render halaman dilakukan jutaan kali, jadi pool variasi tidak boleh di-query
 * per request. Semua dimuat sekali lalu di-cache; cache di-flush saat admin
 * mengubah konten/FAQ.
 */
class ContentRepository
{
    public const CACHE_KEY_BLOCKS = 'content.blocks.bySection';
    public const CACHE_KEY_FAQS = 'content.faqs.byService';

    /**
     * @return array<string, array<int, string>> section => list konten
     */
    public function blocksBySection(): array
    {
        return cache()->remember(self::CACHE_KEY_BLOCKS, 3600, function () {
            $grouped = [];
            ContentBlock::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->get(['section', 'content', 'weight'])
                ->each(function (ContentBlock $block) use (&$grouped) {
                    // weight = berapa kali item muncul di pool (memperbesar peluang terpilih)
                    $repeat = max(1, (int) $block->weight);
                    for ($i = 0; $i < $repeat; $i++) {
                        $grouped[$block->section][] = $block->content;
                    }
                });

            return $grouped;
        });
    }

    /**
     * FAQ untuk sebuah layanan = FAQ global (service_id null) + FAQ khusus layanan.
     *
     * @return array<int, array{question:string, answer:string}>
     */
    public function faqsForService(?int $serviceId): array
    {
        $all = cache()->remember(self::CACHE_KEY_FAQS, 3600, function () {
            return Faq::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['service_id', 'question', 'answer'])
                ->groupBy(fn ($f) => $f->service_id ?? 0)
                ->map->values()
                ->toArray();
        });

        $global = $all[0] ?? [];
        $specific = $serviceId ? ($all[$serviceId] ?? []) : [];

        return array_map(
            fn ($f) => ['question' => $f['question'], 'answer' => $f['answer']],
            array_merge($global, $specific)
        );
    }

    public static function flushCache(): void
    {
        cache()->forget(self::CACHE_KEY_BLOCKS);
        cache()->forget(self::CACHE_KEY_FAQS);
    }
}
