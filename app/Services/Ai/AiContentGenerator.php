<?php

namespace App\Services\Ai;

use App\Models\ContentBlock;
use App\Models\Faq;

/**
 * OTAK pengisian otomatis.
 *
 * Diberi konteks bisnis (brand, jenis usaha, keyword, gaya bahasa), service ini
 * meminta AI menuliskan kalimat variasi untuk SATU section (hero, intro, usp,
 * dst.) atau FAQ, lalu:
 *   1. mem-parse jawaban (JSON array),
 *   2. membuang duplikat (vs DB + vs batch berjalan),
 *   3. menyimpan ke pool (content_blocks / faqs) dalam keadaan aktif.
 *
 * Penting: yang dihasilkan adalah KALIMAT POOL ber-placeholder
 * ({{layanan}} {{kota}} {{kecamatan}} {{kelurahan}} {{brand}}), BUKAN halaman
 * jadi. Mesin VariationEngine yang sudah ada lalu mengombinasikannya menjadi
 * jutaan halaman — gratis, tanpa panggilan AI saat halaman dibuka.
 */
class AiContentGenerator
{
    /** Maksimum item per satu panggilan AI (jaga respons tetap valid & hemat). */
    private const PER_CALL_CAP = 40;

    public function __construct(
        private readonly AiChatClient $client,
        private readonly ?KnowledgeBase $knowledge = null,
    ) {
    }

    /** Label provider aktif (untuk pesan progres ke admin). */
    public function label(): string
    {
        return $this->client->label();
    }

    /**
     * Isi satu section sampai bertambah $need variasi UNIK baru.
     *
     * @param array<string,string> $ctx brand, business, keywords, tone
     * @return array{added:int, tokens:int, calls:int}
     */
    public function fillSection(string $section, array $ctx, int $need): array
    {
        if ($need <= 0 || ! in_array($section, ContentBlock::SECTIONS, true)) {
            return ['added' => 0, 'tokens' => 0, 'calls' => 0];
        }

        $seen = $this->existingSectionSet($section);
        $added = 0;
        $tokens = 0;
        $calls = 0;
        $maxCalls = (int) ceil($need / 15) + 2; // toleransi bila model kurang produktif / banyak duplikat

        while ($added < $need && $calls < $maxCalls) {
            $remaining = $need - $added;
            // Minta lebih banyak dari sisa (buffer 40%) untuk menutup dedup.
            $ask = min(self::PER_CALL_CAP, (int) ceil($remaining * 1.4) + 2);

            [$system, $user] = $this->sectionPrompt($section, $ctx, $ask);
            $res = $this->client->chat($system, $user, ['temperature' => 0.95, 'cache' => true]);
            $tokens += $res['tokens'];
            $calls++;

            foreach ($this->parseStringArray($res['content']) as $line) {
                if ($added >= $need) {
                    break;
                }
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $norm = $this->normalize($line);
                if (isset($seen[$norm])) {
                    continue;
                }
                $seen[$norm] = true;

                ContentBlock::firstOrCreate(
                    ['section' => $section, 'content' => $line],
                    ['weight' => 1, 'is_active' => true],
                );
                $added++;
            }
        }

        return ['added' => $added, 'tokens' => $tokens, 'calls' => $calls];
    }

    /**
     * Isi FAQ global (service_id null) sampai bertambah $need pasang unik.
     *
     * @param array<string,string> $ctx
     * @return array{added:int, tokens:int, calls:int}
     */
    public function fillFaqs(array $ctx, int $need): array
    {
        if ($need <= 0) {
            return ['added' => 0, 'tokens' => 0, 'calls' => 0];
        }

        $seen = [];
        foreach (Faq::query()->pluck('question') as $q) {
            $seen[$this->normalize((string) $q)] = true;
        }
        $sortStart = (int) Faq::query()->max('sort_order') + 1;

        $added = 0;
        $tokens = 0;
        $calls = 0;
        $maxCalls = (int) ceil($need / 8) + 2;

        while ($added < $need && $calls < $maxCalls) {
            $ask = min(self::PER_CALL_CAP, ($need - $added) + 3);
            [$system, $user] = $this->faqPrompt($ctx, $ask);
            $res = $this->client->chat($system, $user, ['temperature' => 0.85, 'cache' => true]);
            $tokens += $res['tokens'];
            $calls++;

            foreach ($this->parseFaqArray($res['content']) as $pair) {
                if ($added >= $need) {
                    break;
                }
                $q = trim((string) ($pair['question'] ?? ''));
                $a = trim((string) ($pair['answer'] ?? ''));
                if ($q === '' || $a === '') {
                    continue;
                }
                $norm = $this->normalize($q);
                if (isset($seen[$norm])) {
                    continue;
                }
                $seen[$norm] = true;

                Faq::create([
                    'service_id' => null,
                    'question' => $q,
                    'answer' => $a,
                    'sort_order' => $sortStart++,
                    'is_active' => true,
                ]);
                $added++;
            }
        }

        return ['added' => $added, 'tokens' => $tokens, 'calls' => $calls];
    }

    // ---------------------------------------------------------------------
    // Prompt builder
    // ---------------------------------------------------------------------

    /**
     * @param array<string,string> $ctx
     * @return array{0:string,1:string} [system, user]
     */
    private function sectionPrompt(string $section, array $ctx, int $count): array
    {
        $spec = $this->sectionSpec($section);
        $ctxText = $this->contextBlock($ctx);

        $system = 'Anda copywriter SEO lokal Indonesia yang sangat berpengalaman. '
            . 'Tulis kalimat pemasaran berbahasa Indonesia yang natural, meyakinkan, '
            . 'dan SANGAT BERVARIASI (hindari pola/kata pembuka yang berulang). '
            . 'Jangan pernah menggunakan tanda kutip pembungkus di dalam teks. '
            . 'Balas HANYA dengan JSON array of strings, tanpa penjelasan, tanpa markdown, tanpa kode fence.';

        $user = $ctxText . "\n\n"
            . "TUGAS: Tulis {$count} variasi untuk bagian \"{$spec['label']}\".\n"
            . $spec['instruction'] . "\n\n"
            . "ATURAN PLACEHOLDER (WAJIB ditulis persis, jangan diterjemahkan):\n"
            . "- Gunakan {{layanan}} {{kota}} {{kecamatan}} {{kelurahan}} {{brand}} secara natural.\n"
            . "- Tidak semua kalimat harus memuat semua placeholder; pilih yang pas.\n"
            . "- Jangan membuat placeholder lain di luar daftar di atas.\n"
            . ($spec['extra'] ?? '')
            . "\nFORMAT OUTPUT: JSON array of strings. Contoh: [\"...\", \"...\"]. "
            . "Hasilkan tepat {$count} item yang semuanya berbeda satu sama lain.";

        return [$system, $user];
    }

    /**
     * @param array<string,string> $ctx
     * @return array{0:string,1:string}
     */
    private function faqPrompt(array $ctx, int $count): array
    {
        $ctxText = $this->contextBlock($ctx);

        $system = 'Anda pakar SEO lokal Indonesia. Tulis FAQ berbahasa Indonesia yang relevan dengan '
            . 'niat pencarian calon pelanggan (harga, jangkauan area, cara pesan, garansi, jadwal, proses). '
            . 'Jawaban singkat, jelas, 1-3 kalimat. '
            . 'Balas HANYA JSON array of objects {"question","answer"} tanpa markdown/penjelasan.';

        $user = $ctxText . "\n\n"
            . "TUGAS: Tulis {$count} pasang FAQ (pertanyaan + jawaban) yang berbeda-beda.\n"
            . "Boleh menyisipkan placeholder {{layanan}} {{kota}} {{kecamatan}} {{kelurahan}} {{brand}} "
            . "bila natural (ditulis persis, jangan diterjemahkan).\n"
            . "FORMAT: [{\"question\":\"...\",\"answer\":\"...\"}, ...]. Tepat {$count} item.";

        return [$system, $user];
    }

    /**
     * @param array<string,string> $ctx
     */
    private function contextBlock(array $ctx): string
    {
        $lines = ['KONTEKS BISNIS:'];
        $lines[] = '- Brand: ' . ($ctx['brand'] ?: '(tanpa nama, gunakan {{brand}})');
        $lines[] = '- Jenis usaha / niche: ' . ($ctx['business'] ?: 'jasa/produk lokal umum');
        if (! empty($ctx['keywords'])) {
            $lines[] = '- Kata kunci / layanan utama: ' . $ctx['keywords'];
        }
        if (! empty($ctx['tone'])) {
            $lines[] = '- Gaya bahasa: ' . $ctx['tone'];
        }
        $lines[] = '- Tujuan halaman: mendatangkan chat WhatsApp dari calon pelanggan di tiap kelurahan.';

        // OTAK MD: sisipkan pengetahuan (brand voice + pengetahuan niche) agar
        // kalimat yang dihasilkan konsisten dengan aturan bisnis, bukan generik.
        if ($this->knowledge) {
            $selalu = $this->knowledge->alwaysOn();
            if ($selalu !== '') {
                $lines[] = '';
                $lines[] = 'ATURAN & GAYA (WAJIB DIPATUHI):';
                $lines[] = $selalu;
            }
            $topik = $this->knowledge->contextFor(($ctx['business'] ?? '') . ' ' . ($ctx['keywords'] ?? ''));
            if ($topik['teks'] !== '') {
                $lines[] = '';
                $lines[] = 'PENGETAHUAN BIDANG:';
                $lines[] = $topik['teks'];
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Spesifikasi tiap section: label + instruksi gaya + aturan tambahan.
     *
     * @return array{label:string, instruction:string, extra?:string}
     */
    private function sectionSpec(string $section): array
    {
        return match ($section) {
            'hero' => [
                'label' => 'Hero (judul besar/headline)',
                'instruction' => 'Judul iklan pendek dan menarik (maksimal ~12 kata) yang menonjolkan {{layanan}} di lokasi. Gaya to-the-point, memicu klik.',
            ],
            'intro' => [
                'label' => 'Intro (paragraf pembuka)',
                'instruction' => 'Satu kalimat pembuka yang berempati dengan situasi/kebutuhan calon pelanggan. Hangat, tidak menjual keras.',
            ],
            'pain_point' => [
                'label' => 'Pain Point (masalah pelanggan)',
                'instruction' => 'Satu kalimat yang menyebut masalah/keluhan nyata yang dirasakan calon pelanggan sebelum memakai layanan.',
            ],
            'solusi' => [
                'label' => 'Solusi',
                'instruction' => 'Satu kalimat yang menjelaskan solusi/cara kerja layanan menjawab masalah tadi. Konkret.',
            ],
            'usp' => [
                'label' => 'USP (keunggulan)',
                'instruction' => 'Frasa/kalimat pendek keunggulan (maksimal ~10 kata). Contoh gaya: "Garansi ganti bila kurang cocok". Tanpa tanda titik di akhir tidak masalah.',
            ],
            'testimoni' => [
                'label' => 'Testimoni',
                'instruction' => 'Kutipan testimoni singkat yang terdengar manusiawi, diakhiri atribusi dengan tanda pisah. '
                    . 'Format: "Isi testimoni. — Peran singkat, {{kota}}" (boleh {{kecamatan}} atau {{kelurahan}}).',
                'extra' => "- Setiap item WAJIB diakhiri pola: \" — <peran>, {{kota}}\" (atau {{kecamatan}}/{{kelurahan}}).\n",
            ],
            'cta' => [
                'label' => 'CTA (ajakan menghubungi)',
                'instruction' => 'Satu kalimat ajakan singkat untuk chat via WhatsApp/konsultasi. Ramah dan mendorong tindakan.',
            ],
            'about' => [
                'label' => 'About (tentang kami)',
                'instruction' => 'Satu-dua kalimat tentang {{brand}} yang membangun kepercayaan (pengalaman, komitmen, jangkauan).',
            ],
            'summary_open' => [
                'label' => 'Summary: kalimat pembuka',
                'instruction' => 'Kalimat pembuka ringkasan yang mengenalkan {{brand}} menyediakan {{layanan}} untuk {{kelurahan}}, {{kecamatan}}, {{kota}}.',
                'extra' => "- Setiap kalimat sebaiknya memuat {{layanan}} dan minimal satu placeholder lokasi.\n",
            ],
            'summary_bridge' => [
                'label' => 'Summary: kalimat jembatan',
                'instruction' => 'Kalimat jembatan yang menonjolkan keunggulan. WAJIB memuat placeholder {{usp_text}} (akan diisi otomatis oleh mesin).',
                'extra' => "- Setiap item WAJIB memuat token {{usp_text}} persis.\n- {{usp_text}} adalah token internal mesin, JANGAN daftarkan di aturan placeholder lain.\n",
            ],
            'summary_close' => [
                'label' => 'Summary: kalimat penutup',
                'instruction' => 'Kalimat penutup ringkasan yang mengajak menghubungi via WhatsApp untuk info harga/ketersediaan di {{kelurahan}}.',
            ],
            'summary_filler' => [
                'label' => 'Summary: kalimat pengisi',
                'instruction' => 'Kalimat netral pelengkap (tanpa lokasi spesifik) untuk menambah panjang ringkasan. Umum berlaku untuk bisnis apa pun.',
            ],
            default => [
                'label' => $section,
                'instruction' => 'Tulis kalimat pemasaran singkat yang relevan.',
            ],
        };
    }

    // ---------------------------------------------------------------------
    // Parsing & dedup
    // ---------------------------------------------------------------------

    /**
     * @return array<int,string>
     */
    private function parseStringArray(string $raw): array
    {
        $data = $this->decodeJson($raw);
        if (! is_array($data)) {
            return [];
        }

        $out = [];
        foreach ($data as $item) {
            if (is_string($item)) {
                $out[] = $item;
            } elseif (is_array($item) && isset($item['content']) && is_string($item['content'])) {
                $out[] = $item['content'];
            }
        }

        return $out;
    }

    /**
     * @return array<int,array{question:string,answer:string}>
     */
    private function parseFaqArray(string $raw): array
    {
        $data = $this->decodeJson($raw);
        if (! is_array($data)) {
            return [];
        }

        $out = [];
        foreach ($data as $item) {
            if (is_array($item) && isset($item['question'], $item['answer'])) {
                $out[] = [
                    'question' => (string) $item['question'],
                    'answer' => (string) $item['answer'],
                ];
            }
        }

        return $out;
    }

    /**
     * Decode JSON toleran: buang code fence, ambil array [ ... ] pertama.
     */
    private function decodeJson(string $raw): mixed
    {
        $raw = trim($raw);
        // Buang ```json ... ``` bila ada.
        $raw = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $raw) ?? $raw;

        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Fallback: ambil substring dari '[' pertama sampai ']' terakhir.
        $start = strpos($raw, '[');
        $end = strrpos($raw, ']');
        if ($start !== false && $end !== false && $end > $start) {
            $slice = substr($raw, $start, $end - $start + 1);
            $decoded = json_decode($slice, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Set (map ternormalisasi => true) dari semua konten aktif+nonaktif section
     * agar tidak menyimpan duplikat yang sama persis maupun mirip.
     *
     * @return array<string,bool>
     */
    private function existingSectionSet(string $section): array
    {
        $set = [];
        foreach (ContentBlock::query()->where('section', $section)->pluck('content') as $c) {
            $set[$this->normalize((string) $c)] = true;
        }

        return $set;
    }

    /**
     * Normalisasi untuk deteksi duplikat: huruf kecil, tanpa placeholder,
     * spasi rapat, tanpa tanda baca tepi.
     */
    private function normalize(string $s): string
    {
        $s = preg_replace('/\{\{\s*[a-zA-Z0-9_]+\s*\}\}/', '', $s) ?? $s; // buang placeholder
        $s = mb_strtolower($s);
        $s = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $s) ?? $s;
        $s = preg_replace('/\s+/', ' ', $s) ?? $s;

        return trim($s);
    }
}
