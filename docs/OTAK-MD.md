# Otak Asisten — Sistem Knowledge (File MD)

Sistem "otak" berbasis file Markdown untuk Asisten SEO. Tujuannya dua:
**membuat asisten lebih pintar & konsisten**, sekaligus **hemat token** karena
hanya file yang relevan yang dikirim ke AI.

## Cara kerja (kunci hemat token)

File MD adalah teks statis; AI tetap harus dikirimi isinya untuk "membaca".
Karena itu tidak semua file dikirim setiap saat. Tiap file punya **pemicu**
(kata kunci). Saat owner bertanya, hanya file yang pemicunya cocok yang disuntik.

Hasil uji nyata (7 file, ~1.641 token total bila semua dikirim):

| Pertanyaan | File terpakai | Hemat |
|---|---|---|
| "aman kalau publish 1 juta halaman?" | seo-playbook, brand-voice, cara-kerja | 49% |
| "kenapa belum dapat lead?" | seo-playbook, brand-voice, konversi-lead | 56% |
| "mapel apa yang paling dicari?" | niche-les-privat, brand-voice | 73% |
| "wilayah mana ekspansi berikutnya?" | strategi-ekspansi, brand-voice | 78% |
| "halo" | brand-voice | 89% |

> Penghematan token yang JAUH lebih besar datang dari **prompt caching**
> (diskon untuk bagian prompt berulang). Bila nanti pemakaian asisten intensif,
> minta saya pasang itu — efeknya lebih besar dari sekadar file MD.

## Lokasi & format

File: `storage/app/brain/*.md`. Format tiap file:

```markdown
---
judul: Kebijakan Konten Google
pemicu: spam, penalti, aman, risiko, jutaan halaman
selalu: false
prioritas: 1
---
(isi markdown bebas…)
```

- **judul** — nama tampil di panel.
- **pemicu** — kata kunci (pisah koma). Bila salah satu muncul di pertanyaan
  owner, file ikut dikirim. Kosongkan bila `selalu: true`.
- **selalu** — `true` = selalu dikirim (untuk aturan inti seperti brand voice).
  Gunakan hemat — makin banyak "selalu", makin sedikit hematnya.
- **prioritas** — angka kecil didahulukan saat knowledge dipangkas (batas
  8.000 karakter per panggilan).

File tanpa front-matter tetap terbaca (dianggap tidak-selalu, tanpa pemicu →
praktis tidak pernah terpakai; selalu isi front-matter).

## 7 file bawaan

| File | Isi | Sifat |
|---|---|---|
| `01-seo-playbook.md` | Kebijakan Google, batas spam, aturan skala | pemicu |
| `02-niche-les-privat.md` | Jenjang, mapel, harga wajar, musim | pemicu |
| `03-brand-voice.md` | Nada, klaim yang boleh/tidak | **selalu** |
| `04-cara-kerja-mesin.md` | Alur import→generate→publish, target, batasan | pemicu |
| `05-strategi-ekspansi.md` | Urutan gelombang wilayah | pemicu |
| `06-konversi-lead.md` | Menaikkan klik WhatsApp | pemicu |
| `07-template-desain.md` | Aturan token & desain salespage | pemicu |

## Cara pakai & memperkaya

1. Taruh isi folder `brain/` ini ke `storage/app/brain/` di server.
2. Buka **Admin → Asisten SEO** — panel "🧩 Otak Asisten" menampilkan semua file
   + pemicunya.
3. Tanya asisten; jawabannya kini berpijak pada knowledge ini.
4. **Menambah pengetahuan**: buat file `.md` baru di folder itu dengan
   front-matter. Langsung terpakai, tanpa deploy ulang.

### Apa yang LAYAK jadi file MD
- Pengetahuan yang tak berubah tiap hari (aturan, prinsip, fakta domain).
- Hal yang Anda ingin asisten konsisten menyebutnya.

### Apa yang TIDAK perlu jadi MD (biar hemat)
- Angka yang berubah (skor, jumlah halaman, klik) — sudah diambil otomatis dari
  database lewat SiteAuditService. Menaruhnya di MD = pemborosan.
- Target teknis (hero 20, dst.) yang sudah ada di kode.

## File
```
BARU   app/Services/Ai/KnowledgeBase.php            (pemilih & pemuat knowledge)
DIUBAH app/Services/Ai/SeoAssistant.php             (suntik knowledge relevan)
DIUBAH app/Http/Controllers/Admin/AssistantController.php  (daftar knowledge)
DIUBAH resources/views/admin/assistant/index.blade.php     (panel Otak Asisten)
BARU   storage/app/brain/*.md                        (7 file pengetahuan awal)
```
Catatan: `storage/app/` biasanya tidak masuk Git. Salin folder `brain/` ke
server secara manual, atau kecualikan dari .gitignore bila ingin ikut repo.
