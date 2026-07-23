{{--
    Layout dasar untuk seluruh halaman publik (beranda, salespage, category).
    Chrome (head, nav, footer, WA float, base CSS + theme fingerprint) diatur di
    sini agar konsisten di semua halaman. Setiap view mengisi:
        @section('content') ...isi halaman... @endsection

    Variabel yang boleh (dan tidak wajib) dilempar oleh view:
        $seo   : array {title, description, canonical, schema, ...}   (dari PageRenderer)
        $css   : string CSS tambahan (dari Template aktif, opsional)
        $js    : string JS tambahan (dari Template aktif, opsional)
        $wa    : string URL WhatsApp lengkap (rotator di salespage)
    Semua opsional; layout jatuh ke default berbasis Settings bila tidak diisi.
--}}
@php
    $s = fn ($k, $d = '') => \App\Models\Setting::get($k, $d);

    $__brand   = $s('brand_name', '');
    $__logoImg = $s('logo_image', '');
    $__tagline = $s('tagline', 'Melayani kebutuhan Anda dengan cepat, terpercaya, dan profesional.');
    $__addr    = $s('contact_address', '');
    $__phone   = $s('contact_phone', '');
    $__email   = $s('contact_email', '');

    // WhatsApp fallback (dipakai bila $wa belum diberikan view — mis. beranda/category).
    // Setting `whatsapp_message` biasanya berisi template dengan token {{layanan}},
    // {{kota}}, dll — yang hanya bisa diresolve di salespage. Untuk halaman tanpa
    // konteks, gunakan pesan default berbasis brand agar tidak muncul teks aneh.
    if (empty($wa ?? null)) {
        $__waNums = \App\Support\WhatsappRotator::numbers($s('whatsapp_number', ''));
        $__waNum = \App\Support\WhatsappRotator::pick(
            $__waNums,
            request()->getPathInfo() ?: '/'
        );

        $__msgRaw = $s('whatsapp_message', '');
        // Jika template berisi token yang hanya bermakna di salespage, pakai default.
        $__hasContextToken = preg_match('/\{\{\s*(layanan|kota|kecamatan|kelurahan)\s*\}\}/', $__msgRaw) === 1;
        if ($__msgRaw === '' || $__hasContextToken) {
            $__msgClean = "Halo {$__brand}, saya ingin konsultasi.";
        } else {
            $__msgClean = \App\Services\TokenReplacer::apply($__msgRaw, ['brand' => $__brand]);
        }
        $__waMsg = rawurlencode($__msgClean);

        $__wa = $__waNum ? "https://wa.me/{$__waNum}?text={$__waMsg}" : '#';
    } else {
        $__wa = $wa;
    }

    // SEO fallback (beranda & category yang tidak lewat PageRenderer).
    $__title     = $seo['title']       ?? ($__brand . ' — ' . $__tagline);
    $__desc      = $seo['description'] ?? \Illuminate\Support\Str::limit(strip_tags($__tagline), 155);
    $__canonical = $seo['canonical']   ?? url()->current();
    $__schema    = $seo['schema']      ?? null;

    // Sinyal sosial & indexing (fallback ke Settings untuk halaman non-salespage).
    $__robots    = $seo['robots']    ?? $s('default_robots', 'index,follow');
    $__ogType    = $seo['type']      ?? 'website';
    $__siteName  = $seo['site_name'] ?? $__brand;
    $__locale    = $seo['locale']    ?? $s('og_locale', 'id_ID');
    $__ogImage   = $seo['image']     ?? ($s('og_image', '') ?: ($__logoImg ?: ''));
    $__ogImgAlt  = $seo['image_alt'] ?? $__title;
    $__themeCol  = $s('theme_color', '#1a9e55');

    // Integrasi Google/Bing (dari Pengaturan).
    $__gsc  = $s('google_site_verification', '');
    $__bing = $s('bing_site_verification', '');
    $__ga   = $s('google_analytics_id', '');
    $__gtm  = $s('gtm_id', '');

    // Tautan hub untuk footer — muncul di SEMUA halaman (beranda, kategori,
    // salespage) sehingga seluruh situs saling menyambung & sinyal terdistribusi
    // ke hub utama. Di-cache agar tidak query per-request.
    // Halaman statis untuk navigasi & footer (di-cache).
    $__nav = cache()->remember('daya.nav.pages', (int) config('daya.category_cache_ttl', 600), function () {
        if (! \Illuminate\Support\Facades\Schema::hasTable('site_pages')) {
            return ['nav' => collect(), 'footer' => collect()];
        }
        $all = \App\Models\SitePage::active()->orderBy('sort_order')->orderBy('title')
            ->get(['slug', 'title', 'menu_label', 'show_in_nav', 'show_in_footer']);

        return ['nav' => $all->where('show_in_nav', true), 'footer' => $all->where('show_in_footer', true)];
    });

    $__hub = cache()->remember('daya.footer.hub', (int) config('daya.category_cache_ttl', 600), function () {
        $services = \App\Models\Service::where('is_active', true)
            ->whereHas('pages', fn ($q) => $q->published())
            ->orderBy('name')->limit(8)->get(['name', 'slug']);
        $firstSlug = optional($services->first())->slug;
        $cities = $firstSlug
            ? \App\Models\City::whereIn('id', \App\Models\Page::published()->select('city_id')->distinct())
                ->orderBy('name')->limit(12)->get(['name', 'slug'])
            : collect();

        return ['services' => $services, 'cities' => $cities, 'firstSlug' => $firstSlug];
    });
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $__title }}</title>
    <meta name="description" content="{{ $__desc }}">
    <link rel="canonical" href="{{ $__canonical }}">
    <meta name="robots" content="{{ $__robots }}">
    <meta name="theme-color" content="{{ $__themeCol }}">

    {{-- Verifikasi Search Console / Bing Webmaster --}}
    @if($__gsc)<meta name="google-site-verification" content="{{ $__gsc }}">@endif
    @if($__bing)<meta name="msvalidate.01" content="{{ $__bing }}">@endif

    {{-- Google Tag Manager --}}
    @if($__gtm)
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $__gtm }}');</script>
    @endif

    {{-- Google Analytics 4 (gtag.js) --}}
    @if($__ga)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $__ga }}"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{{ $__ga }}');</script>
    @endif

    {{-- Open Graph --}}
    <meta property="og:type" content="{{ $__ogType }}">
    <meta property="og:site_name" content="{{ $__siteName }}">
    <meta property="og:locale" content="{{ $__locale }}">
    <meta property="og:title" content="{{ $__title }}">
    <meta property="og:description" content="{{ $__desc }}">
    <meta property="og:url" content="{{ $__canonical }}">
    @if($__ogImage)
        <meta property="og:image" content="{{ $__ogImage }}">
        <meta property="og:image:alt" content="{{ $__ogImgAlt }}">
    @endif

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="{{ $__ogImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $__title }}">
    <meta name="twitter:description" content="{{ $__desc }}">
    @if($__ogImage)<meta name="twitter:image" content="{{ $__ogImage }}">@endif

    {{-- Schema JSON-LD (bila disediakan PageRenderer) --}}
    @if($__schema)
        <script type="application/ld+json">{!! $__schema !!}</script>
    @endif

    {{-- Font Poppins (mengikuti benchmark) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>{!! \App\Support\SalespageStyles::base() !!}</style>
    @if(!empty($css ?? null))<style>{!! $css !!}</style>@endif
    @stack('styles')
</head>
<body class="cegu-page">
    @if($__gtm)<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $__gtm }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>@endif
    <nav class="cegu-nav">
        <div class="in">
            <a href="{{ url('/') }}" class="cegu-brand nav-brand">
                @if($__logoImg)<img src="{{ $__logoImg }}" alt="{{ $__brand }}">@else{{ $__brand }}@endif
            </a>
            <div class="cegu-nav-links">
                <a href="{{ url('/') }}">Beranda</a>
                @forelse($__nav['nav'] as $np)
                    <a href="{{ url('/' . $np->slug) }}">{{ $np->menu_label ?: $np->title }}</a>
                @empty
                    {{-- Belum ada halaman statis: pakai anchor bagian dalam halaman --}}
                    <a href="#keunggulan">Keunggulan</a>
                    <a href="#testimoni">Testimoni</a>
                    <a href="#faq">FAQ</a>
                @endforelse
                <a href="{{ $__wa }}" target="_blank" rel="noopener nofollow" class="cegu-btn green">Konsultasi Gratis</a>
            </div>
        </div>
    </nav>

    @yield('content')

    <footer class="cegu-footer">
        <div class="in">
            <div style="max-width:280px">
                <a href="{{ url('/') }}" class="cegu-brand nav-brand">
                    @if($__logoImg)<img src="{{ $__logoImg }}" alt="{{ $__brand }}">@else{{ $__brand }}@endif
                </a>
                <p style="font-size:.9rem;margin:10px 0 0">{{ $__tagline }}</p>
                @if($__addr)
                    <p style="font-size:.82rem;margin:10px 0 0;opacity:.85">{{ $__addr }}</p>
                @endif
            </div>
            <div>
                <h4>Navigasi</h4>
                <a href="{{ url('/') }}">Beranda</a>
                <a href="{{ url('/') }}#layanan">Layanan</a>
                <a href="{{ url('/') }}#testimoni">Testimoni</a>
                <a href="{{ url('/') }}#faq">FAQ</a>
            </div>
            @if(count($__nav['footer']))
            <div>
                <h4>Informasi</h4>
                @foreach($__nav['footer'] as $fp)
                    <a href="{{ url('/' . $fp->slug) }}">{{ $fp->menu_label ?: $fp->title }}</a>
                @endforeach
            </div>
            @endif
            @if(!empty($__hub['services']) && count($__hub['services']))
            <div>
                <h4>Layanan</h4>
                @foreach($__hub['services'] as $svc)
                    <a href="{{ url('/' . $svc->slug) }}">{{ $svc->name }}</a>
                @endforeach
            </div>
            @endif
            @if(!empty($__hub['cities']) && count($__hub['cities']) && $__hub['firstSlug'])
            <div>
                <h4>Kota</h4>
                @foreach($__hub['cities'] as $ct)
                    <a href="{{ url('/' . $__hub['firstSlug'] . '/' . $ct->slug) }}">{{ $ct->name }}</a>
                @endforeach
            </div>
            @endif
            <div>
                <h4>Kontak</h4>
                <a href="{{ $__wa }}" target="_blank" rel="noopener nofollow">WhatsApp</a>
                @if($__phone)<a href="tel:{{ preg_replace('/\D/', '', $__phone) }}">{{ $__phone }}</a>@endif
                @if($__email)<a href="mailto:{{ $__email }}">{{ $__email }}</a>@endif
                <a href="{{ url('/sitemap.xml') }}">Sitemap</a>
            </div>
        </div>
        <div class="cegu-copy">© {{ now()->year }} {{ $__brand }}. Semua hak dilindungi.</div>
    </footer>

    {{-- CTA WhatsApp mengambang --}}
    <a class="cegu-wa-float" href="{{ $__wa }}" target="_blank" rel="noopener nofollow" aria-label="Hubungi via WhatsApp">
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.5 14.4c-.3-.2-1.7-.9-2-1-.3-.1-.5-.2-.7.1-.2.3-.7 1-.9 1.1-.2.2-.3.2-.6.1-.3-.2-1.2-.5-2.3-1.4-.9-.8-1.4-1.7-1.6-2-.2-.3 0-.5.1-.6l.4-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5 0-.2-.7-1.6-.9-2.2-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.4 0 1.4 1 2.8 1.2 3 .1.2 2 3.1 4.9 4.3.7.3 1.2.5 1.6.6.7.2 1.3.2 1.8.1.5-.1 1.7-.7 1.9-1.4.2-.7.2-1.2.2-1.4-.1-.1-.3-.2-.6-.3zM12 2C6.5 2 2 6.5 2 12c0 1.8.5 3.4 1.3 4.9L2 22l5.3-1.4c1.4.8 3 1.2 4.7 1.2 5.5 0 10-4.5 10-10S17.5 2 12 2zm0 18c-1.5 0-3-.4-4.3-1.2l-.3-.2-3.1.8.8-3-.2-.3C4.4 15 4 13.5 4 12c0-4.4 3.6-8 8-8s8 3.6 8 8-3.6 8-8 8z"/></svg>
        <span class="cegu-wa-float-txt">Chat WhatsApp<small>Konsultasi Gratis</small></span>
    </a>

    {{-- Pelacakan klik WhatsApp: nomor CS mana (rotator) + apakah benar-benar terbuka --}}
    <script>
    (function () {
        var seg = location.pathname.replace(/^\/|\/$/g, '').split('/');
        var svc = seg[0] ? decodeURIComponent(seg[0]).replace(/-/g, ' ') : '';
        var city = seg[1] ? decodeURIComponent(seg[1]).replace(/-/g, ' ') : '';
        var url = "{{ route('track.wa') }}";
        var lastToken = null, lastAt = 0;

        function source(el) {
            if (el.closest('.cegu-wa-float')) return 'float';
            if (el.closest('.cegu-nav')) return 'nav';
            return 'inline';
        }

        // Ambil nomor CS langsung dari tautan yang diklik (paling akurat —
        // mengikuti rotator apa pun yang sedang dipakai halaman ini).
        function waNumber(href) {
            var m = href.match(/(?:wa\.me\/|phone=)(\d{6,20})/);
            return m ? m[1] : '';
        }

        function kirim(payload) {
            try {
                navigator.sendBeacon(url, new Blob([JSON.stringify(payload)], { type: 'application/json' }));
            } catch (err) {}
        }

        document.addEventListener('click', function (e) {
            var a = e.target.closest('a[href*="wa.me"], a[href*="api.whatsapp.com"], a[href*="whatsapp.com/send"]');
            if (!a) return;

            var src = source(a);
            var nomor = waNumber(a.getAttribute('href') || '');
            lastToken = Math.random().toString(36).slice(2) + Date.now().toString(36);
            lastAt = Date.now();

            // 1) Event GA4 (bila tersedia).
            try {
                var d = { service: svc, city: city, source: src, wa_number: nomor, page_path: location.pathname };
                if (typeof gtag === 'function') { gtag('event', 'whatsapp_click', d); }
                else if (window.dataLayer) { d.event = 'whatsapp_click'; window.dataLayer.push(d); }
            } catch (err) {}

            // 2) Beacon ke server.
            kirim({ path: location.pathname, service: svc, city: city, source: src, wa_number: nomor, token: lastToken });
        }, true);

        // 3) Penanda "WhatsApp benar-benar terbuka": bila halaman berpindah ke
        //    latar belakang dalam 10 detik setelah klik, aplikasi/tab WhatsApp
        //    kemungkinan besar terbuka. Ini PROKSI, bukan kepastian.
        document.addEventListener('visibilitychange', function () {
            if (document.hidden && lastToken && (Date.now() - lastAt) < 10000) {
                kirim({ confirm: lastToken });
                lastToken = null;
            }
        });
    })();
    </script>

    @if(!empty($js ?? null))<script>{!! $js !!}</script>@endif
    @stack('scripts')
</body>
</html>
