{{--
    Beranda — memakai layout yang sama dengan salespage (chrome nav/footer/head
    identik) DAN mengikuti FORMASI section yang sama seperti template salespage
    default (mirror benchmark privatnusantara.com):

        Hero + Stats
        → Standar Layanan (4 pilar)
        → Layanan Kami (grid services dari DB)
        → Kelebihan Layanan (6 fitur unggulan)
        → Testimoni
        → FAQ (bila ada FAQ global, service_id = null)
        → Wilayah Kami Layani (auto dari kota yang punya halaman published)
        → Tentang (opsional, bila diisi di Settings)
        → CTA final

    Semua tulisan default bisa dioverride dari Panel Admin → Pengaturan.
--}}
@extends('layouts.site')

@section('content')
@php
    $s = fn ($k, $d = '') => \App\Models\Setting::get($k, $d);

    $brand    = $s('brand_name', '');
    $heroImg  = $s('hero_image', '');
    $tagline  = $s('tagline', 'Melayani kebutuhan Anda dengan cepat, terpercaya, dan profesional.');

    // WhatsApp beranda ikut memakai rotator publik agar distribusi lead konsisten.
    $waNums = \App\Support\WhatsappRotator::numbers($s('whatsapp_number', ''));
    $waNum  = \App\Support\WhatsappRotator::pick($waNums, request()->getPathInfo() ?: '/');
    // Pakai helper agar token {{layanan}}/{{kota}} dari setting tidak bocor
    // ke pesan WhatsApp (beranda tidak punya konteks lokasi).
    $waLink = \App\Support\WaLink::generic($brand);
    if ($waLink === '#') { $waLink = '#layanan'; }

    // Hero
    $heroEyebrow = $s('home_hero_eyebrow') ?: 'Layanan Profesional';
    $heroTitle   = $s('home_hero_title')   ?: 'Layanan Terpercaya dengan Kualitas Terbaik';
    $heroLead    = $s('home_hero_lead')    ?: $tagline;

    // Stats (angka kepercayaan — bebas edit di Pengaturan).
    $stat1n = $s('home_stat1_num', '9/10'); $stat1l = $s('home_stat1_label', 'Tingkat Kepuasan Pelanggan');
    $stat2n = $s('home_stat2_num', '100+'); $stat2l = $s('home_stat2_label', 'Pelanggan Terlayani');
    $stat3n = $s('home_stat3_num', '95%');  $stat3l = $s('home_stat3_label', 'Merekomendasikan Kami');

    // Testimoni default (bisa dioverride via Settings home_testi1..3 + who).
    $testimoniList = collect([1, 2, 3])->map(fn ($i) => [
        'quote' => $s("home_testi{$i}") ?: [
            1 => 'Layanan sangat memuaskan, respon cepat, dan hasilnya sesuai harapan. Sangat direkomendasikan.',
            2 => 'Harga bersaing dan pekerjaan rapi. Tim datang tepat waktu dan komunikatif dari awal.',
            3 => 'Konsultasi jelas, tidak bertele-tele. Puas dengan hasilnya, akan pakai lagi ke depan.',
        ][$i],
        'who' => $s("home_testi{$i}_who") ?: [
            1 => 'Ibu Rina — Jakarta',
            2 => 'Bapak Anwar — Bekasi',
            3 => 'Ibu Sari — Depok',
        ][$i],
    ]);

    // Wilayah — otomatis dari kota yang sudah punya halaman published.
    // Link diarahkan ke /{first_service_slug}/{city_slug} (halaman pilih kecamatan).
    $firstService = $services->first();
    $kotaList = $firstService
        ? \App\Models\City::query()
            ->whereIn('id', \App\Models\Page::published()->select('city_id')->distinct())
            ->orderBy('name')
            ->limit(16)
            ->get(['name', 'slug'])
        : collect();

    // FAQ generik (service_id = null) — cocok untuk beranda.
    // Skip FAQ yang mengandung token {{layanan}}/{{kota}}/{{kecamatan}}/{{kelurahan}}
    // karena token tersebut hanya bisa diresolve di konteks salespage.
    $faqs = \App\Models\Faq::query()
        ->where('is_active', true)
        ->whereNull('service_id')
        ->orderBy('sort_order')
        ->get(['question', 'answer'])
        ->reject(fn ($f) => preg_match(
            '/\{\{\s*(layanan|kota|kecamatan|kelurahan)\s*\}\}/',
            $f->question . ' ' . $f->answer
        ) === 1)
        ->take(6);

    // Tentang & CTA
    $aboutDesc = $s('home_about_desc') ?: $s('home_owner_desc');
    $ctaTitle  = $s('home_cta_title')  ?: 'Siap Memulai?';
    $ctaLead   = $s('home_cta_lead')   ?: 'Konsultasi gratis untuk menentukan solusi terbaik bagi Anda.';
@endphp

{{-- ============================== HERO + STATS ============================== --}}
<header class="cegu-hero">
    <div class="in">
        <div class="cegu-hero-grid">
            <div class="cegu-hero-text">
                <span class="cegu-eyebrow">{{ $heroEyebrow }}</span>
                <h1>{{ $heroTitle }}</h1>
                <p class="lead">{{ $heroLead }}</p>
                <div class="cegu-hero-cta">
                    <a class="cegu-btn light" href="{{ $waLink }}" target="_blank" rel="noopener nofollow">
                        <span aria-hidden="true">💬</span> Konsultasi Gratis
                    </a>
                    <a class="cegu-btn" href="#layanan">Lihat Layanan</a>
                </div>
            </div>
            @if($heroImg)
                <div class="cegu-hero-media">
                    <img class="cegu-hero-img" src="{{ $heroImg }}" alt="{{ $heroTitle }}"
                         loading="eager" fetchpriority="high" decoding="async">
                </div>
            @endif
        </div>
        <div class="cegu-stats">
            <div class="cegu-stat"><div class="n">{{ $stat1n }}</div><div class="l">{{ $stat1l }}</div></div>
            <div class="cegu-stat"><div class="n">{{ $stat2n }}</div><div class="l">{{ $stat2l }}</div></div>
            <div class="cegu-stat"><div class="n">{{ $stat3n }}</div><div class="l">{{ $stat3l }}</div></div>
        </div>
    </div>
</header>

{{-- ============================== 4 PILAR STANDAR ============================== --}}
<section class="cegu-section" style="padding-top:74px">
    <div class="in">
        <div class="cegu-head">
            <span class="cegu-eyebrow">Kenapa {{ $brand }}</span>
            <h2>Standar Layanan Kami</h2>
            <p>Cara kami menjaga kualitas dan kepercayaan di setiap layanan.</p>
        </div>
        <div class="cegu-grid">
            <div class="cegu-tile">
                <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg></div>
                <h3>Track Record Terbukti</h3>
                <p>Rekam jejak layanan yang teruji dan dapat dipertanggungjawabkan.</p>
            </div>
            <div class="cegu-tile">
                <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7L9 18l-5-5"/></svg></div>
                <h3>Tim yang Kompeten</h3>
                <p>Setiap anggota tim dipilih sesuai keahlian dan kebutuhan pelanggan.</p>
            </div>
            <div class="cegu-tile">
                <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l8 4v6c0 5-3.5 8-8 10-4.5-2-8-5-8-10V6z"/></svg></div>
                <h3>Integritas Terjaga</h3>
                <p>Mengutamakan kejujuran dan kualitas di setiap interaksi dengan pelanggan.</p>
            </div>
            <div class="cegu-tile">
                <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 14l4-4 3 3 5-6"/></svg></div>
                <h3>Evaluasi Berkala</h3>
                <p>Masukan pelanggan dipantau rutin sebagai dasar peningkatan layanan.</p>
            </div>
        </div>
    </div>
</section>

{{-- ============================== LAYANAN KAMI ============================== --}}
<section id="layanan" class="cegu-section alt">
    <div class="in">
        <div class="cegu-head">
            <span class="cegu-eyebrow">Layanan</span>
            <h2>Layanan Kami</h2>
            <p>Pilih layanan dan lihat ketersediaan di wilayah Anda.</p>
        </div>
        @if($services->isEmpty())
            <p style="text-align:center" class="cegu-about">Belum ada layanan yang dipublikasikan.</p>
        @else
            <div class="cegu-grid">
                @foreach($services as $service)
                    <a class="cegu-tile" style="text-decoration:none;color:inherit" href="{{ url('/' . $service->slug) }}">
                        <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V5a2 2 0 0 1 2-2h9l5 5v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><path d="M8 8h6M8 12h8"/></svg></div>
                        <h3>{{ $service->name }}</h3>
                        <p>Lihat ketersediaan per wilayah →</p>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>

{{-- ============================== 6 FITUR UNGGULAN ============================== --}}
<section class="cegu-section">
    <div class="in">
        <div class="cegu-head">
            <span class="cegu-eyebrow">Fitur Unggulan</span>
            <h2>Kelebihan Layanan Kami</h2>
            <p>Semua yang Anda butuhkan untuk pengalaman layanan terbaik.</p>
        </div>
        <div class="cegu-grid">
            <div class="cegu-tile">
                <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 12 0v1"/></svg></div>
                <h3>Tim Profesional</h3>
                <p>Ditangani tenaga pilihan yang berpengalaman di bidangnya.</p>
            </div>
            <div class="cegu-tile">
                <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v12H7l-3 3z"/></svg></div>
                <h3>Informasi Transparan</h3>
                <p>Anda mendapat informasi yang jelas di setiap tahap, plus konsultasi gratis.</p>
            </div>
            <div class="cegu-tile">
                <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V5a2 2 0 0 1 2-2h9l5 5v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><path d="M8 8h6M8 12h8M8 16h5"/></svg></div>
                <h3>Layanan Fleksibel</h3>
                <p>Layanan disesuaikan dengan kebutuhan dan kondisi Anda.</p>
            </div>
            <div class="cegu-tile">
                <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/></svg></div>
                <h3>Transaksi Mudah</h3>
                <p>Pembayaran mudah dan aman lewat berbagai pilihan pembayaran.</p>
            </div>
            <div class="cegu-tile">
                <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11a9 9 0 0 1 18 0v5a3 3 0 0 1-3 3h-1v-6h4M6 19H5a3 3 0 0 1-3-3v0"/></svg></div>
                <h3>Support Responsif</h3>
                <p>Bantuan cepat dari tim kami kapan pun Anda membutuhkan.</p>
            </div>
            <div class="cegu-tile">
                <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg></div>
                <h3>Menjangkau Wilayah Anda</h3>
                <p>Layanan tersedia di berbagai kota besar di seluruh Indonesia.</p>
            </div>
        </div>
    </div>
</section>

{{-- ============================== TESTIMONI ============================== --}}
<section id="testimoni" class="cegu-section alt">
    <div class="in">
        <div class="cegu-head">
            <span class="cegu-eyebrow">Testimoni</span>
            <h2>Apa Kata Mereka</h2>
        </div>
        <div class="cegu-testi-grid">
            @foreach($testimoniList as $t)
                <figure class="cegu-testi">
                    <blockquote>{{ $t['quote'] }}</blockquote>
                    <figcaption style="margin-top:14px;font-weight:600;font-size:.88rem;color:var(--muted)">{{ $t['who'] }}</figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================== FAQ (opsional) ============================== --}}
@if($faqs->isNotEmpty())
<section id="faq" class="cegu-section">
    <div class="in">
        <div class="cegu-head">
            <span class="cegu-eyebrow">FAQ</span>
            <h2>Pertanyaan yang Sering Diajukan</h2>
        </div>
        <div class="cegu-faq">
            @foreach($faqs as $f)
                <details class="cegu-faq-item">
                    <summary>{{ $f->question }}</summary>
                    <div class="cegu-faq-answer">{{ $f->answer }}</div>
                </details>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============================== WILAYAH KAMI LAYANI ============================== --}}
@if($kotaList->isNotEmpty() && $firstService)
<section class="cegu-section alt">
    <div class="in">
        <div class="cegu-head">
            <span class="cegu-eyebrow">Jangkauan</span>
            <h2>Wilayah yang Kami Layani</h2>
            <p>Layanan tersedia di berbagai kota besar di Indonesia — pilih kota Anda.</p>
        </div>
        <div class="cegu-internal-links" style="grid-template-columns:1fr">
            <div class="cegu-links-col">
                <ul style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr))">
                    @foreach($kotaList as $kota)
                        <li><a href="{{ url('/' . $firstService->slug . '/' . $kota->slug) }}">{{ $kota->name }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>
@endif

{{-- ============================== TENTANG (opsional) ============================== --}}
@if($aboutDesc)
<section class="cegu-section">
    <div class="in cegu-about">
        <div class="cegu-head">
            <span class="cegu-eyebrow">Tentang Kami</span>
            <h2>Tentang {{ $brand }}</h2>
        </div>
        <div class="cegu-summary">{{ $aboutDesc }}</div>
    </div>
</section>
@endif

{{-- ============================== CTA FINAL ============================== --}}
<section class="cegu-section">
    <div class="in">
        <div class="cegu-cta-final">
            <h2>{{ $ctaTitle }}</h2>
            <p>{{ $ctaLead }}</p>
            <a class="cegu-btn green" href="{{ $waLink }}" target="_blank" rel="noopener nofollow">
                <span aria-hidden="true">💬</span> Konsultasi Gratis via WhatsApp
            </a>
        </div>
    </div>
</section>
@endsection
