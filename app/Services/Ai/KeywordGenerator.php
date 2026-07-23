<?php

namespace App\Services\Ai;

/**
 * Generator keyword LONGTAIL (dimensi "layanan") via AI.
 *
 * Untuk "menggempur" Indonesia, mesin mengalikan daftar layanan × lokasi.
 * Lokasi diambil dari dataset wilayah RESMI (bukan dikarang). Yang paling
 * berguna dihasilkan AI adalah variasi keyword layanan/intent — itulah yang
 * dibuat di sini. Keyword sengaja TANPA nama kota (kota ditambах cross-join).
 */
class KeywordGenerator
{
    public function __construct(private readonly AiChatClient $client)
    {
    }

    /**
     * @param array{business:string, seeds?:string, count?:int} $ctx
     * @return array<int,string> Daftar keyword unik (huruf kecil).
     */
    public function generate(array $ctx, int $count = 100): array
    {
        $business = trim($ctx['business'] ?? '');
        $seeds = trim($ctx['seeds'] ?? '');
        $count = max(10, min(400, $count));

        $system = 'Anda pakar SEO longtail Bahasa Indonesia. Hasilkan keyword pencarian '
            . 'yang NATURAL dipakai calon pelanggan, mencakup ragam intent '
            . '(informasional, komersial, transaksional) dan modifier seperti '
            . 'murah, terbaik, terdekat, panggilan, 24 jam, bergaransi, harga, '
            . 'profesional, serta segmen (mis. SD/SMP/SMA/anak/dewasa/pemula bila relevan). '
            . 'ATURAN: JANGAN sertakan nama kota/daerah (lokasi ditambахkan terpisah). '
            . 'Semua huruf kecil, tanpa tanda kutip. '
            . 'Balas HANYA JSON array of strings, tanpa penjelasan/markdown.';

        $user = "Niche/usaha: {$business}\n"
            . ($seeds !== '' ? "Kata kunci awal (kembangkan & variasikan): {$seeds}\n" : '')
            . "Buat {$count} keyword longtail berbeda-beda untuk niche ini. "
            . "Fokus keyword yang realistis dicari orang. FORMAT: [\"...\", \"...\"].";

        $res = $this->client->chat($system, $user, ['temperature' => 0.9, 'cache' => true]);

        return $this->clean($this->parse($res['content']));
    }

    public function label(): string
    {
        return $this->client->label();
    }

    /**
     * @return array<int,string>
     */
    private function parse(string $raw): array
    {
        $raw = trim($raw);
        $raw = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $raw) ?? $raw;
        $data = json_decode($raw, true);
        if (! is_array($data)) {
            $start = strpos($raw, '[');
            $end = strrpos($raw, ']');
            if ($start !== false && $end !== false && $end > $start) {
                $data = json_decode(substr($raw, $start, $end - $start + 1), true);
            }
        }

        return is_array($data) ? $data : [];
    }

    /**
     * @param array<int,mixed> $items
     * @return array<int,string>
     */
    private function clean(array $items): array
    {
        $out = [];
        $seen = [];
        foreach ($items as $item) {
            if (! is_string($item)) {
                continue;
            }
            $kw = mb_strtolower(trim($item));
            $kw = trim($kw, "\"'.,;:-–—");
            $kw = preg_replace('/\s+/', ' ', $kw) ?? $kw;
            if ($kw === '' || mb_strlen($kw) < 3) {
                continue;
            }
            if (isset($seen[$kw])) {
                continue;
            }
            $seen[$kw] = true;
            $out[] = $kw;
        }

        return $out;
    }
}
