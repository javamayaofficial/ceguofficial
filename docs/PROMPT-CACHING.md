# Prompt Caching — Penghematan Token Terbesar

Pelengkap sistem "Otak MD". Sementara file MD menghemat dengan **mengirim lebih
sedikit**, prompt caching menghemat dengan **tidak membayar penuh untuk bagian
yang berulang**.

## Cara kerjanya

Setiap panggilan asisten mengirim bagian yang SAMA berulang-ulang: aturan peran,
daftar aksi, brand voice. Prompt caching menandai bagian ini agar dihitung
sekali; panggilan berikutnya dalam jendela waktu singkat hanya membayar tarif
"cache read" — di Anthropic sekitar **1/10 harga token input biasa**.

## Yang diubah

1. **`AnthropicClient`** — dukungan `cache_control` (opsi `cache => true`).
   Menandai blok `system` sebagai cache-able + menghitung token cache untuk
   pemantauan biaya.
2. **`SeoAssistant`** — knowledge ber-flag `selalu: true` (brand voice)
   dipindah ke `system` prompt yang stabil, sehingga ikut ter-cache. Knowledge
   berpemicu tetap di `user` (memang berubah tiap pertanyaan).
3. **`AiContentGenerator` & `KeywordGenerator`** — `cache => true` diaktifkan;
   system prompt panjang mereka (aturan penulisan) berulang tiap section.

## Efek gabungan (perkiraan)

Untuk sesi diskusi asisten (mis. 10 pertanyaan beruntun):

| Bagian | Tanpa optimasi | Dengan MD + cache |
|---|---|---|
| Aturan + brand voice (system) | dibayar penuh tiap pesan | dibayar penuh 1x, sisanya ~10% |
| Knowledge topik | semua dikirim | hanya yang relevan |
| Snapshot situs | penuh tiap pesan | penuh (memang berubah) |

Kombinasi keduanya bisa memangkas biaya input hingga **60–80%** pada sesi
panjang — jauh di atas MD saja.

## Catatan penting

- **Otomatis untuk OpenAI & DeepSeek**: keduanya melakukan caching sendiri tanpa
  parameter khusus, ASALKAN bagian statis ada di awal prompt (sudah diatur).
  Flag `cache` kita hanya berpengaruh nyata di Anthropic native, tapi aman
  dikirim ke semua (OpenAI-compatible client mengabaikannya).
- **Lewat OpenRouter**: caching diteruskan sesuai model tujuan. Untuk model
  Anthropic via OpenRouter, gunakan `AI_DRIVER=anthropic` bila ingin kontrol
  cache_control eksplisit; via `openrouter` tetap dapat caching otomatis model.
- Cache bersifat sementara (ephemeral, ~5 menit di Anthropic). Menghemat pada
  penggunaan beruntun, bukan panggilan yang jarang & terpencar.

## File
```
DIUBAH app/Services/Ai/AnthropicClient.php     (cache_control + hitung token cache)
DIUBAH app/Services/Ai/SeoAssistant.php        (knowledge selalu -> system)
DIUBAH app/Services/Ai/KnowledgeBase.php        (alwaysOn() + contextFor tanpa selalu)
DIUBAH app/Services/Ai/AiContentGenerator.php  (cache => true)
DIUBAH app/Services/Ai/KeywordGenerator.php    (cache => true)
```
Semua backward-compatible: tanpa kunci AI, semua tetap no-op seperti biasa.
