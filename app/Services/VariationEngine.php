<?php

namespace App\Services;

/**
 * Pemilih variasi DETERMINISTIK.
 *
 * Inti dari "Formula Kombinasi" (PDF 2): tiap halaman punya variation_seed unik.
 * Dengan seed yang sama + pool yang sama, pilihan selalu sama (reproducible),
 * sehingga halaman terlihat unik namun stabil tanpa perlu menyimpan HTML atau
 * memanggil AI eksternal.
 */
class VariationEngine
{
    public function __construct(private readonly int $seed)
    {
    }

    public static function forSeed(int $seed): self
    {
        return new self($seed);
    }

    /**
     * Pilih satu item dari list secara deterministik berdasar seed + nama section.
     *
     * @template T
     * @param array<int, T> $items
     * @return T|null
     */
    public function pick(string $section, array $items): mixed
    {
        $items = array_values($items);
        if (empty($items)) {
            return null;
        }

        $index = $this->hash($section) % count($items);

        return $items[$index];
    }

    /**
     * Pilih beberapa item unik secara deterministik (untuk USP, testimoni, dst).
     *
     * @param array<int, mixed> $items
     * @return array<int, mixed>
     */
    public function pickMany(string $section, array $items, int $count): array
    {
        $items = array_values($items);
        $total = count($items);
        if ($total === 0) {
            return [];
        }
        $count = min($count, $total);

        // Urutkan ulang index secara deterministik (Fisher-Yates ber-seed), lalu ambil N pertama.
        $order = range(0, $total - 1);
        $rngState = $this->hash($section . ':order');
        for ($i = $total - 1; $i > 0; $i--) {
            $rngState = $this->lcg($rngState);
            $j = $rngState % ($i + 1);
            [$order[$i], $order[$j]] = [$order[$j], $order[$i]];
        }

        $picked = [];
        for ($k = 0; $k < $count; $k++) {
            $picked[] = $items[$order[$k]];
        }

        return $picked;
    }

    /**
     * Hash stabil 31-bit dari seed + label (tidak bergantung platform).
     */
    private function hash(string $label): int
    {
        return (int) (sprintf('%u', crc32($this->seed . '|' . $label)) % 2147483647);
    }

    /**
     * Linear congruential generator sederhana untuk pseudo-acak deterministik.
     */
    private function lcg(int $state): int
    {
        return (int) (($state * 1103515245 + 12345) & 0x7FFFFFFF);
    }
}
