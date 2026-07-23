# Integrasi AI — "Isi Otomatis Pool Konten sampai Hijau"

Fitur ini menambahkan tombol **🤖 Isi Otomatis dengan AI** di halaman
**Admin → Variasi Konten**. AI menuliskan kalimat variasi (hero, intro, USP,
testimoni, dst.) + FAQ **sampai semua indikator kesehatan hijau**, membuang
duplikat otomatis, lewat antrian (queue).

Prinsipnya: AI mengisi **pool** (`content_blocks` / `faqs`), **bukan** halaman
per-halaman. Mesin `VariationEngine` yang sudah ada mengombinasikan pool jadi
jutaan halaman — jadi biaya AI **sekali bayar per niche**, bukan membengkak
seiring jumlah halaman. Saat halaman dibuka, **tidak ada** panggilan AL.

---

## 1. File yang ditambahkan

```
app/Services/Ai/AiChatClient.php            # kontrak provider-agnostik
app/Services/Ai/AiException.php
app/Services/Ai/OpenAiCompatibleClient.php  # OpenAI/DeepSeek/Groq/OpenRouter/lokal
app/Services/Ai/AnthropicClient.php         # Claude
app/Services/Ai/AiClientFactory.php         # pilih driver dari config
app/Services/Ai/AiContentGenerator.php      # otak: generate + dedup + simpan
app/Support/AiFillProgress.php              # status progres (di cache)
app/Jobs/GenerateContentBlocksJob.php       # jalankan di antrian sampai hijau
```

## 2. File yang diubah

- `config/services.php` — blok `'ai'` (driver/key/model/base_url/timeout).
- `app/Providers/AppServiceProvider.php` — bind `AiChatClient` → factory.
- `app/Http/Controllers/Admin/ContentBlockController.php` — method `aiFill()` + `aiStatus()`.
- `routes/web.php` — route `content/ai-fill` (POST) & `content/ai-status` (GET).
- `resources/views/admin/content/index.blade.php` — kartu tombol + bar progres.
- `.env.example` — variabel `AI_*`.

---

## 3. Konfigurasi (.env)

Pilih salah satu penyedia. DeepSeek populer & murah untuk pasar Indonesia;
OpenAI paling stabil; Anthropic (Claude) untuk kualitas tulisan tinggi.

```env
# DeepSeek (murah)
AI_DRIVER=deepseek
AI_API_KEY=sk-xxxxxxxx
AI_MODEL=deepseek-chat

# atau OpenAI
# AI_DRIVER=openai
# AI_API_KEY=sk-xxxxxxxx
# AI_MODEL=gpt-4o-mini

# atau Anthropic (Claude)
# AI_DRIVER=anthropic
# AI_API_KEY=sk-ant-xxxxxxxx
# AI_MODEL=claude-sonnet-4-6

# Endpoint lain (Together/Mistral/Ollama lokal): AI_DRIVER=custom + AI_BASE_URL=...
```

Setelah mengubah `.env`:

```bash
php artisan config:clear
```

**Keamanan:** kunci API HANYA di `.env` (server), tidak pernah di database
atau panel admin. Jangan commit `.env` ke Git.

---

## 4. Cara pakai

1. Pastikan worker antrian berjalan:
   ```bash
   php artisan queue:work
   ```
   (Di produksi: jalankan lewat Supervisor — lihat `deploy/`.)
2. Buka **Admin → Variasi Konten**.
3. Di kartu **🤖 Isi Otomatis dengan AI**, isi *Jenis usaha/niche*
   (mis. "les privat", "jasa service AC", "herbal", "agen properti"),
   opsional kata kunci & gaya bahasa, pilih kekayaan pool, lalu **Isi Otomatis**.
4. Bar progres berjalan; halaman auto-refresh saat selesai. Indikator kesehatan
   akan menghijau. Bila ada bagian belum penuh (model banyak menghasilkan
   duplikat), klik **Isi Otomatis** sekali lagi.

Biaya sekali isi (hingga hijau) ± 160 kalimat + 10 FAQ — hanya beberapa
panggilan API, sangat kecil.

---

## 5. Kontrak placeholder (penting)

Kalimat hasil AI memakai token yang WAJIB ditulis persis:
`{{layanan}} {{kota}} {{kecamatan}} {{kelurahan}} {{brand}}` — dan khusus
`summary_bridge` memuat `{{usp_text}}` (diisi mesin). Prompt sudah mengunci ini,
tapi selalu tinjau sampel sebelum publish massal.

---

## 6. Catatan skala (baca sebelum produksi jutaan halaman)

Kebijakan **"scaled content abuse"** Google (2024) menyasar halaman yang dibuat
massal terutama untuk memanipulasi peringkat — terlepas dibuat AI atau template.
Agar aman & tahan lama:

- Pakai AI untuk **memperkaya**, bukan mengarang kosong. Kombinasikan dengan
  **data lokal riil** (kolom CSV `extra`: harga, jumlah_tutor, landmark, dll.)
  yang sudah didukung mesin — inilah pembeda nyata per halaman.
- **Bertahap:** mulai 1 kota, submit sitemap, ukur indexing di Search Console,
  baru skalakan. Publish bergelombang (fitur ini sudah ada), jangan sekaligus.
- Granularitas kota/kecamatan yang berisi lebih aman daripada jutaan kelurahan
  yang tipis.
