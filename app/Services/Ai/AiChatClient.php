<?php

namespace App\Services\Ai;

/**
 * Kontrak client AI (provider-agnostik).
 *
 * Mesin CEGU tidak terikat ke satu penyedia. Semua driver (OpenAI-compatible,
 * Anthropic, dst.) hanya perlu mengimplementasikan satu method: kirim system +
 * user prompt, terima teks jawaban + jumlah token yang dipakai (untuk pantau
 * biaya). Dengan begini, ganti provider = ganti satu baris di .env, bukan
 * bongkar kode.
 */
interface AiChatClient
{
    /**
     * @param string $system Instruksi peran/aturan untuk model.
     * @param string $user Perintah/isi permintaan.
     * @param array{temperature?:float,max_tokens?:int} $opts
     * @return array{content:string, tokens:int} Teks jawaban + total token (0 bila tak tersedia).
     *
     * @throws AiException Bila panggilan gagal (kunci salah, kuota habis, jaringan, dll).
     */
    public function chat(string $system, string $user, array $opts = []): array;

    /**
     * Label provider aktif (untuk log & pesan ke admin).
     */
    public function label(): string;
}
