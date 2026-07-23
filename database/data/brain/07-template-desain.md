---
judul: Aturan Template & Desain Salespage
pemicu: template, desain, tampilan, layout, section, token, blok, kondisional, hero, faq, halaman kacau, bocor
selalu: false
prioritas: 4
---
# Template Salespage

## Aturan token
- Gunakan HANYA token standar {{token}} yang didukung mesin.
- HINDARI sintaks {{#...}} / {{! ...}} kecuali TokenReplacer versi baru sudah
  terpasang di server — bila belum, sintaks itu tampil MENTAH di halaman.
- Token tak didukung/kosong otomatis dibersihkan oleh PageRenderer.

## Prinsip desain yang baik untuk pSEO lokal
- Satu H1, sisanya H2/H3 rapi.
- Data lokal ditaruh tinggi (pembeda utama).
- CTA WhatsApp tersebar, bukan hanya di bawah.
- Jangan menaruh 200+ kata identik di semua halaman (boilerplate) -> pakai
  {{usp_list}} dsb. yang bervariasi.
- Statistik hero harus jujur (kualitatif bila tak ada angka riil).
