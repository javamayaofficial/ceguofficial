<?php

namespace App\Services\Ai;

/**
 * ASISTEN SEO SENIOR — bisa diajak diskusi DAN eksekusi.
 *
 * Cara kerja:
 *  1. Setiap pertanyaan owner dikirim ke AI bersama SNAPSHOT NYATA situs
 *     (dari SiteAuditService), sehingga jawabannya berbasis angka, bukan tebakan.
 *  2. AI menjawab sebagai konsultan SEO senior berbahasa Indonesia awam.
 *  3. Bila perlu tindakan, AI TIDAK mengeksekusi sendiri — ia MENGUSULKAN aksi
 *     dari daftar tertutup (whitelist). Owner menekan tombol untuk menjalankan.
 *
 * Keputusan desain (penting): asisten sengaja TIDAK diberi kuasa eksekusi
 * langsung. Aksi seperti publish massal berdampak besar & sulit dibatalkan,
 * jadi keputusan akhir tetap di tangan manusia. Ini pola "usulkan → konfirmasi".
 */
class SeoAssistant
{
    /**
     * Daftar aksi yang BOLEH diusulkan AI. Apa pun di luar ini diabaikan.
     *
     * @var array<string,array{label:string,desc:string,danger:bool}>
     */
    public const ACTIONS = [
        'isi_konten' => [
            'label' => 'Isi variasi konten dengan AI',
            'desc' => 'Menambah kalimat variasi + FAQ sampai indikator hijau.',
            'danger' => false,
        ],
        'generate_keyword' => [
            'label' => 'Buat keyword longtail',
            'desc' => 'Menghasilkan daftar keyword layanan untuk cross-join.',
            'danger' => false,
        ],
        'warm_cache' => [
            'label' => 'Panaskan cache halaman',
            'desc' => 'Pra-render halaman agar cepat saat dikunjungi/crawl.',
            'danger' => false,
        ],
        'submit_indexnow' => [
            'label' => 'Kirim URL ke IndexNow',
            'desc' => 'Mendorong halaman published agar cepat terindeks.',
            'danger' => false,
        ],
        'buka_pengaturan' => [
            'label' => 'Buka halaman Pengaturan',
            'desc' => 'Untuk melengkapi konfigurasi yang kurang.',
            'danger' => false,
        ],
        'buka_import' => [
            'label' => 'Buka halaman Import',
            'desc' => 'Untuk mengunggah data lokasi/layanan.',
            'danger' => false,
        ],
        'buka_publish' => [
            'label' => 'Buka dashboard publish',
            'desc' => 'Publish halaman bertahap (keputusan tetap di owner).',
            'danger' => true,
        ],
    ];

    public function __construct(
        private readonly AiChatClient $client,
        private readonly SiteAuditService $audit,
        private readonly KnowledgeBase $knowledge,
    ) {
    }

    public function label(): string
    {
        return $this->client->label();
    }

    /**
     * Jawab pertanyaan owner dengan konteks kondisi situs.
     *
     * @param array<int,array{role:string,content:string}> $history
     * @return array{jawaban:string, aksi:array<int,array{id:string,label:string,alasan:string}>, tokens:int}
     */
    public function ask(string $question, array $history = []): array
    {
        $snapshot = $this->audit->fullAudit();

        $system = $this->systemPrompt();

        // Knowledge "selalu aktif" (mis. brand voice) ditempel ke SYSTEM agar
        // ikut ter-cache — dihitung sekali, bukan tiap pesan. Knowledge lain
        // yang dipicu pertanyaan tetap di user prompt (berubah-ubah).
        $selalu = $this->knowledge->alwaysOn();
        if ($selalu !== '') {
            $system .= "\n\n== PENGETAHUAN TETAP ==\n" . $selalu;
        }

        $user = $this->buildUserPrompt($question, $snapshot, $history);

        // cache => true: aktifkan prompt caching (system prompt yang berulang
        // dihitung sekali). Hemat besar untuk asisten yang dipakai berkali-kali.
        $res = $this->client->chat($system, $user, ['temperature' => 0.4, 'max_tokens' => 2000, 'cache' => true]);
        $parsed = $this->parse($res['content']);

        return [
            'jawaban' => $parsed['jawaban'],
            'aksi' => $parsed['aksi'],
            'tokens' => $res['tokens'],
        ];
    }

    /**
     * Laporan kesehatan lengkap untuk owner (tanpa pertanyaan spesifik).
     *
     * @return array{jawaban:string, aksi:array<int,mixed>, tokens:int, snapshot:array<string,mixed>}
     */
    public function report(): array
    {
        $snapshot = $this->audit->fullAudit();

        $question = 'Buatkan LAPORAN KESEHATAN WEBSITE untuk pemilik bisnis (bukan orang teknis). '
            . 'Susun: (1) Ringkasan kondisi dalam 2-3 kalimat, (2) Yang sudah baik, '
            . '(3) Masalah yang harus diperbaiki beserta dampaknya ke bisnis, '
            . '(4) Rekomendasi 30 hari ke depan dengan urutan prioritas. '
            . 'Gunakan bahasa sederhana dan sebutkan angka nyata dari data.';

        $res = $this->ask($question);
        $res['snapshot'] = $snapshot;

        return $res;
    }

    // -----------------------------------------------------------------

    private function systemPrompt(): string
    {
        $actions = [];
        foreach (self::ACTIONS as $id => $a) {
            $actions[] = "- {$id}: {$a['desc']}";
        }
        $actionList = implode("\n", $actions);

        return <<<PROMPT
Anda adalah SEO Specialist senior yang memegang sebuah website programmatic SEO
(pSEO) untuk bisnis lokal di Indonesia. Anda berbicara langsung kepada PEMILIK
bisnis yang awam teknis.

PRINSIP ANDA:
- Jujur dan berbasis data. Gunakan angka dari DATA SITUS yang diberikan. Jangan
  mengarang angka. Bila data tidak tersedia, katakan apa adanya.
- Bahasa Indonesia sederhana. Hindari jargon; bila terpaksa, jelaskan singkat.
- Fokus pada dampak BISNIS (lead/chat WhatsApp), bukan sekadar metrik teknis.
- Prioritaskan: perbaiki yang kritis dulu, baru ekspansi.
- Anda TIDAK menyarankan taktik berisiko: konten massal tanpa nilai, review/rating
  palsu, cloaking, atau membeli backlink. Bila owner memintanya, tolak dengan sopan
  dan jelaskan risikonya (bisa kena penalti Google), lalu tawarkan alternatif aman.
- Bila owner ingin menerbitkan jutaan halaman sekaligus, ingatkan untuk bertahap
  dan perkaya data lokal — ini demi keselamatan domain mereka sendiri.

PEMBAGIAN KERJA (SANGAT PENTING):
Anda mengerjakan yang bisa diotomatiskan (menulis kalimat konten, membuat
keyword, memicu proses lewat tombol aksi). Namun ADA HAL YANG TIDAK BISA ANDA
KERJAKAN dan mutlak butuh owner, yaitu:
  a) rahasia & akses server — kunci API, isi file .env, perintah shell;
  b) klaim kepemilikan — verifikasi Google Search Console;
  c) fakta bisnis nyata — nomor WhatsApp, harga asli, testimoni riil, foto/logo;
  d) keputusan berisiko — persetujuan publish massal.
Lihat "tugas_owner" pada DATA SITUS untuk daftar terkini.

ATURAN: jangan pernah berpura-pura sudah mengerjakan hal di atas, dan jangan
diam bila terhambat olehnya. SEBUTKAN DENGAN JELAS apa yang Anda butuhkan dari
owner, KENAPA Anda tidak bisa melakukannya sendiri, dan LANGKAH KONKRET yang
harus mereka lakukan (menu/perintah persisnya). Bila sebuah rekomendasi
tertahan karena menunggu owner, katakan terus terang bahwa itu blocker.

AKSI YANG BISA ANDA USULKAN (hanya id di bawah ini, jangan mengarang id lain):
{$actionList}

FORMAT JAWABAN — balas HANYA JSON valid, tanpa markdown/code fence:
{
  "jawaban": "teks jawaban Anda (boleh beberapa paragraf, boleh pakai '-' untuk poin)",
  "aksi": [{"id": "isi_konten", "alasan": "kenapa ini disarankan"}]
}
"aksi" boleh kosong [] bila tidak ada tindakan yang perlu. Maksimal 3 aksi,
urut dari yang paling penting.
PROMPT;
    }

    /**
     * @param array<string,mixed> $snapshot
     * @param array<int,array{role:string,content:string}> $history
     */
    private function buildUserPrompt(string $question, array $snapshot, array $history): string
    {
        $json = json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $prompt = '';

        // OTAK: suntik hanya knowledge (file MD) yang relevan dengan pertanyaan.
        // File yang tak relevan tidak dikirim → tidak jadi token yang dibayar.
        $k = $this->knowledge->contextFor($question);
        if ($k['teks'] !== '') {
            $prompt .= "PENGETAHUAN ACUAN (pegang ini sebagai dasar jawaban):\n{$k['teks']}\n\n";
        }

        $prompt .= "DATA SITUS (kondisi nyata saat ini):\n{$json}\n\n";

        if (! empty($history)) {
            $prompt .= "PERCAKAPAN SEBELUMNYA:\n";
            foreach (array_slice($history, -6) as $h) {
                $who = ($h['role'] ?? '') === 'assistant' ? 'Anda' : 'Owner';
                $prompt .= "{$who}: " . mb_substr((string) ($h['content'] ?? ''), 0, 800) . "\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "PERTANYAAN OWNER:\n{$question}";

        return $prompt;
    }

    /**
     * @return array{jawaban:string, aksi:array<int,array{id:string,label:string,alasan:string,danger:bool}>}
     */
    private function parse(string $raw): array
    {
        $raw = trim($raw);
        $raw = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $raw) ?? $raw;

        $data = json_decode($raw, true);
        if (! is_array($data)) {
            $start = strpos($raw, '{');
            $end = strrpos($raw, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $data = json_decode(substr($raw, $start, $end - $start + 1), true);
            }
        }

        // Bila model tetap membalas teks biasa, tampilkan apa adanya (jangan gagal).
        if (! is_array($data) || ! isset($data['jawaban'])) {
            return ['jawaban' => $raw !== '' ? $raw : 'Maaf, jawaban tidak terbaca. Coba ulangi pertanyaan.', 'aksi' => []];
        }

        $aksi = [];
        foreach ((array) ($data['aksi'] ?? []) as $a) {
            $id = is_array($a) ? (string) ($a['id'] ?? '') : (string) $a;
            if (! isset(self::ACTIONS[$id])) {
                continue; // buang aksi di luar whitelist
            }
            $aksi[] = [
                'id' => $id,
                'label' => self::ACTIONS[$id]['label'],
                'alasan' => is_array($a) ? (string) ($a['alasan'] ?? '') : '',
                'danger' => self::ACTIONS[$id]['danger'],
            ];
            if (count($aksi) >= 3) {
                break;
            }
        }

        return ['jawaban' => (string) $data['jawaban'], 'aksi' => $aksi];
    }
}
