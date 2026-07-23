<?php

namespace App\Support;

/**
 * CSS dasar salespage — struktur SERAGAM untuk semua produk/bisnis.
 *
 * Mesin ini dipakai lintas niche, jadi tata letaknya sengaja sama. Yang
 * membedakan tiap instalasi:
 *   1. WARNA  → diatur di Pengaturan (lihat BrandColors)
 *   2. LOGO & GAMBAR section → diunggah lewat Pengaturan
 *   3. Fingerprint tema → variasi halus antar instalasi (anti-footprint)
 *
 * Palet di coreCss() hanyalah NILAI BAWAAN, akan ditimpa bila admin mengisi
 * warna sendiri. Admin juga tetap bisa override lewat CSS template.
 */
class SalespageStyles
{
    public static function base(): string
    {
        // Varian visual per instalasi (palet/radius/font) — anti-footprint.
        return self::coreCss() . "\n" . ThemeFingerprint::cssOverrides()
            . "\n" . BrandColors::cssOverrides();
    }

    private static function coreCss(): string
    {
        return <<<'CSS'
:root{
  /* Palet BAWAAN — ditimpa oleh warna dari Pengaturan (lihat BrandColors) */
  --g:#2b8a99;        /* teal sedang — hero, tombol, judul (teks putih terbaca) */
  --gd:#1f6a76;       /* teal gelap — gradient & hover */
  --teal-bright:#3aa8b8; /* teal cerah (warna logo) — logo, aksen, ikon */
  --gold:#f5a623;     /* oranye hangat — angka & CTA */
  --red:#e8543f;      /* koral hangat — tombol sekunder */
  --bg:#f2fbfc;--card:#fff;--ink:#173d44;--muted:#5a7178;--line:#d5edf0;--radius:16px;
}
*{box-sizing:border-box}
body.cegu-page{margin:0;font-family:"Poppins",-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;color:var(--ink);background:var(--bg);line-height:1.65}
img{max-width:100%}
a{color:var(--g)}
.cegu-container{max-width:1080px;margin:0 auto;padding:0 20px}

/* Navbar */
.cegu-nav{position:sticky;top:0;z-index:40;background:#fff;border-bottom:1px solid var(--line)}
.cegu-nav .in{display:flex;align-items:center;justify-content:space-between;height:64px;max-width:1080px;margin:0 auto;padding:0 20px}
.cegu-brand{font-weight:800;font-size:1.25rem;color:var(--g);text-decoration:none;letter-spacing:-.01em}
.nav-brand{display:inline-flex;align-items:center}
.nav-brand img{height:44px;width:auto;border-radius:8px;display:block}
.cegu-footer .nav-brand img{height:40px}
.cegu-brand span{color:var(--gold)}
.cegu-nav-links{display:flex;gap:22px;align-items:center}
.cegu-nav-links a{color:var(--ink);text-decoration:none;font-size:.92rem;font-weight:500}
.cegu-nav-links a:hover{color:var(--g)}
.cegu-nav .cegu-btn{padding:9px 20px;font-size:.85rem}
@media(max-width:780px){.cegu-nav-links a:not(.cegu-btn){display:none}}

/* Buttons */
.cegu-btn{display:inline-flex;align-items:center;gap:8px;background:var(--red);color:#fff;text-decoration:none;font-weight:600;font-size:.95rem;padding:13px 28px;border-radius:30px;border:0;cursor:pointer;transition:transform .15s,box-shadow .15s}
.cegu-btn:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(168,0,0,.25)}
.cegu-btn.green{background:var(--g);color:#fff}.cegu-btn.green:hover{box-shadow:0 8px 20px rgba(4,83,35,.3)}
.cegu-nav .cegu-btn.green{background:var(--g);color:#fff}
.cegu-nav .cegu-btn,.cegu-nav .cegu-btn.green,.cegu-nav .cegu-btn span{color:#fff}
.cegu-btn.gold{background:var(--gold);color:var(--gd)}
.cegu-btn.light{background:#fff;color:var(--g)}.cegu-btn.light:hover{box-shadow:0 8px 24px rgba(0,0,0,.18)}

/* Hero */
.cegu-hero{background:linear-gradient(135deg,var(--g),var(--gd));color:#fff;padding:54px 0 0}
.cegu-hero .in{max-width:1080px;margin:0 auto;padding:0 20px}
.cegu-breadcrumb{font-size:.8rem;color:rgba(255,255,255,.75);margin-bottom:18px}
.cegu-breadcrumb a{color:rgba(255,255,255,.9);text-decoration:none}
.cegu-breadcrumb .sep{margin:0 6px;opacity:.5}
.cegu-eyebrow{display:inline-block;background:rgba(242,195,0,.18);color:var(--gold);font-weight:600;font-size:.78rem;letter-spacing:.08em;text-transform:uppercase;padding:6px 14px;border-radius:30px;margin-bottom:16px}
.cegu-hero h1{font-size:2.45rem;line-height:1.15;margin:0 0 16px;font-weight:800;max-width:760px}
.cegu-hero h1 b{color:var(--gold)}
.cegu-hero .lead{font-size:1.1rem;color:rgba(255,255,255,.88);max-width:620px;margin:0 0 26px}
.cegu-hero-cta{display:flex;gap:12px;flex-wrap:wrap;align-items:center;padding-bottom:40px}
/* Hero 2 kolom: teks + gambar */
.cegu-hero-grid{display:flex;gap:34px;align-items:center;flex-wrap:wrap}
.cegu-hero-text{flex:1 1 380px;min-width:0}
.cegu-hero-text h1,.cegu-hero-text .lead{max-width:none}
.cegu-hero-media{flex:1 1 300px;display:flex;justify-content:center}
.cegu-hero-img{width:100%;max-width:460px;max-height:340px;object-fit:cover;border-radius:18px;box-shadow:0 16px 40px rgba(0,0,0,.28);background:#fff}
@media(max-width:780px){.cegu-hero-media{display:none}}
/* Stats strip */
.cegu-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:var(--line);border-radius:var(--radius);overflow:hidden;transform:translateY(28px);box-shadow:0 18px 40px rgba(4,83,35,.18)}
.cegu-stat{background:#fff;padding:22px 18px;text-align:center}
.cegu-stat .n{font-size:1.9rem;font-weight:800;color:var(--g);line-height:1}
.cegu-stat .l{font-size:.82rem;color:var(--muted);margin-top:6px}

/* Sections */
.cegu-section{padding:58px 0}
.cegu-section.alt{background:#fff}
.cegu-section .in{max-width:1080px;margin:0 auto;padding:0 20px}
.cegu-head{text-align:center;max-width:680px;margin:0 auto 34px}
.cegu-head .cegu-eyebrow{background:rgba(4,83,35,.08);color:var(--g)}
.cegu-head h2{font-size:1.8rem;font-weight:800;color:var(--g);margin:0 0 10px}
.cegu-head p{color:var(--muted);margin:0}

/* Card grids */
.cegu-grid{display:grid;gap:18px;grid-template-columns:repeat(auto-fit,minmax(240px,1fr))}
.cegu-tile{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:24px 22px}
.cegu-tile .ico{width:46px;height:46px;border-radius:12px;background:rgba(4,83,35,.08);color:var(--g);display:flex;align-items:center;justify-content:center;margin-bottom:14px}
.cegu-tile .ico svg{width:24px;height:24px}
.cegu-tile h3{font-size:1.05rem;margin:0 0 6px;font-weight:700;color:var(--ink)}
.cegu-tile p{margin:0;color:var(--muted);font-size:.92rem}

/* Plain lists (pain/solusi/usp tokens) styled as checklist cards */
.cegu-painpoints,.cegu-solusi,.cegu-usp{list-style:none;padding:0;margin:0;display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(260px,1fr))}
.cegu-painpoints li,.cegu-solusi li,.cegu-usp li{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:14px 16px 14px 46px;position:relative;font-size:.95rem}
.cegu-painpoints li::before,.cegu-solusi li::before,.cegu-usp li::before{position:absolute;left:14px;top:13px;width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700}
.cegu-solusi li::before{content:"✓";background:rgba(4,83,35,.12);color:var(--g)}
.cegu-usp li::before{content:"★";background:rgba(242,195,0,.22);color:#9a7b00}
.cegu-painpoints li::before{content:"!";background:rgba(168,0,0,.1);color:var(--red)}

/* Testimonials */
.cegu-section-img{margin:0 auto 28px;max-width:820px;border-radius:var(--radius);overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,.08)}
.cegu-section-img img{display:block;width:100%;height:auto;object-fit:cover}
.cegu-fakta{list-style:none;margin:18px 0 0;padding:16px 20px;background:var(--card);border:1px solid var(--line);border-radius:var(--radius);display:grid;gap:8px;grid-template-columns:repeat(auto-fit,minmax(240px,1fr))}
.cegu-fakta li{font-size:.95rem}
.cegu-fakta strong{color:var(--gold)}
.cegu-testi-grid{display:grid;gap:18px;grid-template-columns:repeat(auto-fit,minmax(260px,1fr))}
.cegu-testi{margin:0;background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:22px;position:relative}
.cegu-testi::before{content:"\201C";font-size:3rem;line-height:1;color:var(--gold);font-family:Georgia,serif;position:absolute;top:8px;right:18px;opacity:.5}
.cegu-testi blockquote{margin:0;color:#3a473e;font-size:.95rem}

/* FAQ */
.cegu-faq{max-width:780px;margin:0 auto;display:grid;gap:12px}
.cegu-faq-item{border:1px solid var(--line);border-radius:12px;background:var(--card);padding:2px 20px}
.cegu-faq-item summary{cursor:pointer;font-weight:600;padding:15px 0;list-style:none;display:flex;justify-content:space-between;gap:12px}
.cegu-faq-item summary::after{content:"+";color:var(--g);font-weight:700;font-size:1.2rem}
.cegu-faq-item[open] summary::after{content:"\2212"}
.cegu-faq-answer{padding:0 0 16px;color:var(--muted);font-size:.93rem}

/* Summary / about */
.cegu-about{max-width:820px;margin:0 auto;text-align:center}
.cegu-summary{background:#fff;border:1px solid var(--line);border-left:4px solid var(--gold);border-radius:12px;padding:20px 22px;color:#3a473e;font-size:.96rem;text-align:left}

/* Internal links */
.cegu-internal-links{display:grid;gap:22px;grid-template-columns:1fr;max-width:880px;margin:0 auto}
@media(min-width:640px){.cegu-internal-links{grid-template-columns:1fr 1fr}}
.cegu-links-col h3{font-size:1rem;margin:0 0 10px;color:var(--g)}
.cegu-links-col ul{list-style:none;padding:0;margin:0;display:grid;gap:8px}
.cegu-links-col a{color:var(--ink);text-decoration:none;font-size:.92rem;display:inline-flex;gap:8px;align-items:center}
.cegu-links-col a::before{content:"→";color:var(--gold)}
.cegu-links-col a:hover{color:var(--g)}

/* Final CTA band */
.cegu-cta-final{background:linear-gradient(135deg,var(--gold),#f6d44a);color:var(--gd);border-radius:var(--radius);padding:44px 28px;text-align:center;max-width:1040px;margin:0 auto}
.cegu-cta-final h2{margin:0 0 10px;font-size:1.7rem;font-weight:800;color:var(--gd)}
.cegu-cta-final p{margin:0 0 22px;color:#5c4d00}

/* Footer */
.cegu-footer{background:var(--gd);color:rgba(255,255,255,.8);padding:40px 0 26px;margin-top:58px}
.cegu-footer .in{max-width:1080px;margin:0 auto;padding:0 20px;display:flex;flex-wrap:wrap;gap:24px;justify-content:space-between}
.cegu-footer .cegu-brand{color:#fff}
.cegu-footer a{color:rgba(255,255,255,.8);text-decoration:none;display:block;margin:6px 0;font-size:.9rem}
.cegu-footer a:hover{color:var(--gold)}
.cegu-footer h4{color:#fff;font-size:.95rem;margin:0 0 8px}
.cegu-copy{border-top:1px solid rgba(255,255,255,.12);margin-top:22px;padding-top:18px;text-align:center;font-size:.82rem;color:rgba(255,255,255,.6)}

/* Floating WA */
.cegu-wa-float{position:fixed;right:20px;bottom:20px;display:inline-flex;align-items:center;gap:10px;background:linear-gradient(135deg,#25d366,#20ba5a);color:#fff;text-decoration:none;font-weight:700;padding:14px 22px;border-radius:40px;box-shadow:0 10px 30px rgba(37,211,102,.5);z-index:50;transition:transform .2s,box-shadow .2s;animation:waPulse 2.4s ease-in-out infinite}
.cegu-wa-float::before{content:"";position:absolute;inset:0;border-radius:40px;box-shadow:0 0 0 0 rgba(37,211,102,.5);animation:waRing 2.4s ease-out infinite;z-index:-1}
.cegu-wa-float:hover{transform:translateY(-3px) scale(1.04);box-shadow:0 14px 38px rgba(37,211,102,.6);animation-play-state:paused}
.cegu-wa-float svg{width:26px;height:26px;flex-shrink:0}
.cegu-wa-float-txt{font-size:.95rem;letter-spacing:.01em}
.cegu-wa-float-txt small{display:block;font-size:.68rem;font-weight:500;opacity:.9;line-height:1}
@keyframes waPulse{0%,100%{transform:translateY(0)}50%{transform:translateY(-4px)}}
@keyframes waRing{0%{box-shadow:0 0 0 0 rgba(37,211,102,.5)}70%{box-shadow:0 0 0 16px rgba(37,211,102,0)}100%{box-shadow:0 0 0 0 rgba(37,211,102,0)}}
@media(prefers-reduced-motion:reduce){.cegu-wa-float,.cegu-wa-float::before{animation:none}}

@media(max-width:640px){
  .cegu-hero h1{font-size:1.75rem}
  .cegu-section{padding:42px 0}
  .cegu-head h2{font-size:1.45rem}
  .cegu-stat .n{font-size:1.5rem}
  .cegu-wa-float-txt{display:none}
}
CSS;
    }
}
