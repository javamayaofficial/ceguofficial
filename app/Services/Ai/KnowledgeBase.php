<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\File;

/**
 * "OTAK" berbasis file Markdown — knowledge base untuk Asisten SEO.
 *
 * Konsep hemat token yang benar: file MD TIDAK dikirim semuanya setiap saat.
 * Setiap file diberi "pemicu" (kata kunci di front-matter). Saat owner bertanya,
 * hanya file yang relevan yang disuntik ke prompt. Sisanya tidak dikirim →
 * tidak jadi token yang dibayar.
 *
 * Struktur file (storage/app/brain/*.md):
 *
 *   ---
 *   judul: Kebijakan Konten Google
 *   pemicu: spam, penalti, konten massal, duplikat, aman, risiko
 *   selalu: false          # true = selalu ikut (mis. brand voice)
 *   prioritas: 1           # makin kecil makin didahulukan saat dipangkas
 *   ---
 *   (isi markdown…)
 *
 * File tanpa front-matter tetap terbaca (dianggap 'selalu: false', tanpa pemicu).
 */
class KnowledgeBase
{
    /** Batas total karakter knowledge yang disuntik per panggilan (jaga biaya). */
    private const MAX_CHARS = 8000;

    private string $dir;

    public function __construct(?string $dir = null)
    {
        $this->dir = $dir ?? storage_path('app/brain');
    }

    /**
     * Semua file knowledge yang tersedia (untuk ditampilkan di panel).
     *
     * @return array<int,array{file:string,judul:string,pemicu:array<int,string>,selalu:bool,chars:int}>
     */
    public function list(): array
    {
        if (! File::isDirectory($this->dir)) {
            return [];
        }

        $out = [];
        foreach (File::glob($this->dir . '/*.md') as $path) {
            $meta = $this->parse(File::get($path));
            $out[] = [
                'file' => basename($path),
                'judul' => $meta['judul'] ?: basename($path),
                'pemicu' => $meta['pemicu'],
                'selalu' => $meta['selalu'],
                'chars' => mb_strlen($meta['isi']),
            ];
        }

        usort($out, fn ($a, $b) => strcmp($a['file'], $b['file']));

        return $out;
    }

    /**
     * Knowledge yang ber-flag `selalu: true` saja — untuk ditempel ke system
     * prompt (stabil, ikut ter-cache).
     */
    public function alwaysOn(): string
    {
        if (! File::isDirectory($this->dir)) {
            return '';
        }

        $teks = '';
        foreach (File::glob($this->dir . '/*.md') as $path) {
            $meta = $this->parse(File::get($path));
            if ($meta['selalu']) {
                $teks .= "### {$meta['judul']}\n" . trim($meta['isi']) . "\n\n";
            }
        }

        return trim($teks);
    }

    /**
     * Pilih knowledge yang relevan dengan pertanyaan, lalu rakit jadi satu blok
     * teks siap suntik. Hanya file BERPEMICU (yang `selalu` sudah masuk system).
     *
     * @return array{teks:string, dipakai:array<int,string>, chars:int}
     */
    public function contextFor(string $question): array
    {
        if (! File::isDirectory($this->dir)) {
            return ['teks' => '', 'dipakai' => [], 'chars' => 0];
        }

        $q = mb_strtolower($question);
        $cocok = [];

        foreach (File::glob($this->dir . '/*.md') as $path) {
            $meta = $this->parse(File::get($path));

            if ($meta['selalu']) {
                continue; // sudah ditempel ke system prompt
            }

            $relevan = false;
            foreach ($meta['pemicu'] as $kata) {
                if ($kata !== '' && str_contains($q, mb_strtolower($kata))) {
                    $relevan = true;
                    break;
                }
            }

            if ($relevan) {
                $cocok[] = [
                    'file' => basename($path),
                    'judul' => $meta['judul'] ?: basename($path),
                    'isi' => trim($meta['isi']),
                    'prioritas' => $meta['prioritas'],
                    'selalu' => false,
                ];
            }
        }

        // Urut prioritas kecil dulu.
        usort($cocok, fn ($a, $b) => $a['prioritas'] <=> $b['prioritas']);

        // Rakit sampai batas MAX_CHARS.
        $teks = '';
        $dipakai = [];
        foreach ($cocok as $c) {
            $blok = "### {$c['judul']}\n{$c['isi']}\n\n";
            if (mb_strlen($teks) + mb_strlen($blok) > self::MAX_CHARS) {
                break;
            }
            $teks .= $blok;
            $dipakai[] = $c['file'];
        }

        return ['teks' => trim($teks), 'dipakai' => $dipakai, 'chars' => mb_strlen($teks)];
    }

    /**
     * Parse front-matter sederhana + isi.
     *
     * @return array{judul:string,pemicu:array<int,string>,selalu:bool,prioritas:int,isi:string}
     */
    private function parse(string $raw): array
    {
        $meta = ['judul' => '', 'pemicu' => [], 'selalu' => false, 'prioritas' => 5, 'isi' => $raw];

        if (preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $raw, $m)) {
            $meta['isi'] = $m[2];
            foreach (preg_split('/\n/', $m[1]) as $line) {
                if (! str_contains($line, ':')) {
                    continue;
                }
                [$k, $v] = array_map('trim', explode(':', $line, 2));
                $k = mb_strtolower($k);
                if ($k === 'judul') {
                    $meta['judul'] = $v;
                } elseif ($k === 'pemicu') {
                    $meta['pemicu'] = array_values(array_filter(array_map('trim', explode(',', $v))));
                } elseif ($k === 'selalu') {
                    $meta['selalu'] = in_array(mb_strtolower($v), ['true', 'ya', '1', 'yes'], true);
                } elseif ($k === 'prioritas') {
                    $meta['prioritas'] = (int) $v ?: 5;
                }
            }
        }

        return $meta;
    }
}
