<?php

namespace App\Services;

use App\Models\ContentBlock;
use App\Models\Faq;
use App\Support\RenderCache;

/**
 * Paket Konten Awal per jenis bisnis (MULTI-NICHE).
 *
 * Satu mesin untuk produk/jasa apa pun: pendidikan, jasa umum, herbal,
 * properti, dan seterusnya. Admin cukup memilih paket → pool variasi + FAQ
 * terisi kalimat yang sesuai niche-nya, siap dipakai dan siap dikembangkan.
 *
 * Memuat paket bersifat ADITIF: menambah variasi baru tanpa menghapus yang
 * sudah ada (duplikat persis dilewati). Ganti niche total = hapus variasi
 * lama secara manual lalu muat paket baru.
 */
class ContentPackService
{
    /**
     * @return array<string,string> slug => label
     */
    public function available(): array
    {
        $packs = [];
        foreach (glob($this->dir() . '/*.json') ?: [] as $file) {
            $slug = basename($file, '.json');
            $data = json_decode((string) file_get_contents($file), true);
            $packs[$slug] = (string) ($data['label'] ?? $slug);
        }
        ksort($packs);

        return $packs;
    }

    /**
     * @return array{blocks:int, faqs:int}
     */
    public function load(string $slug): array
    {
        $file = $this->dir() . '/' . basename($slug) . '.json';
        if (! is_file($file)) {
            throw new \InvalidArgumentException("Paket konten \"{$slug}\" tidak ditemukan.");
        }

        $data = json_decode((string) file_get_contents($file), true);
        if (! is_array($data)) {
            throw new \RuntimeException("Paket konten \"{$slug}\" tidak valid.");
        }

        $blocks = 0;
        foreach (($data['content_blocks'] ?? []) as $section => $items) {
            if (! in_array($section, ContentBlock::SECTIONS, true)) {
                continue;
            }
            foreach ($items as $content) {
                $created = ContentBlock::firstOrCreate(
                    ['section' => $section, 'content' => $content],
                    ['weight' => 1, 'is_active' => true]
                );
                if ($created->wasRecentlyCreated) {
                    $blocks++;
                }
            }
        }

        $faqs = 0;
        foreach (($data['faqs'] ?? []) as $i => $faq) {
            $created = Faq::firstOrCreate(
                ['service_id' => null, 'question' => $faq['question']],
                ['answer' => $faq['answer'], 'sort_order' => $i, 'is_active' => true]
            );
            if ($created->wasRecentlyCreated) {
                $faqs++;
            }
        }

        ContentRepository::flushCache();
        RenderCache::bump();

        return ['blocks' => $blocks, 'faqs' => $faqs];
    }

    private function dir(): string
    {
        return database_path('seeders/data/packs');
    }
}
