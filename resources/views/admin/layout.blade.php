<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $__engine = \App\Models\Setting::get('engine_name') ?: config('daya.engine_name', 'CEGU');
        $__engineLogo = trim((string) \App\Models\Setting::get('engine_logo', ''));
    @endphp
    <title>@yield('title', 'Admin') — {{ $__engine }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{
            --p:#4f46e5;--p-soft:#eef2ff;--g:#16a34a;--red:#dc2626;
            --warn:#d97706;--info:#0ea5e9;
            --dark:#0b1220;--dark-2:#111a2e;--muted:#64748b;--bg:#f6f8fb;--card:#fff;--border:#e6ebf2;
            --side-w:250px;--radius:14px;
            --shadow-sm:0 1px 2px rgba(15,23,42,.04),0 1px 3px rgba(15,23,42,.06);
            --shadow:0 4px 16px rgba(15,23,42,.06);
        }
        *{box-sizing:border-box}
        body{margin:0;font-family:"Inter",-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;
             background:var(--bg);color:#0f172a;-webkit-font-smoothing:antialiased;letter-spacing:-.005em}
        a{color:var(--p)}
        .wrap{display:flex;min-height:100vh}

        /* ---------- SIDEBAR ---------- */
        .side{width:var(--side-w);background:linear-gradient(180deg,var(--dark),var(--dark-2));
              color:#94a3b8;flex-shrink:0;position:sticky;top:0;height:100vh;overflow-y:auto;padding-bottom:22px}
        .side::-webkit-scrollbar{width:6px}
        .side::-webkit-scrollbar-thumb{background:#1e293b;border-radius:3px}
        .side .brand{display:flex;align-items:center;gap:9px;font-weight:800;color:#fff;
                     padding:20px 20px 16px;font-size:1.05rem;letter-spacing:-.02em;
                     border-bottom:1px solid rgba(148,163,184,.12);margin-bottom:6px;
                     position:sticky;top:0;background:var(--dark);z-index:2}
        .side .brand .dot{width:26px;height:26px;border-radius:8px;flex-shrink:0;
                          background:linear-gradient(135deg,var(--p),#818cf8);display:grid;place-items:center;
                          font-size:.8rem;box-shadow:0 2px 8px rgba(79,70,229,.4)}
        .side a{display:flex;align-items:center;gap:10px;color:#94a3b8;text-decoration:none;
                padding:9px 20px;font-size:.88rem;font-weight:500;border-left:3px solid transparent;
                transition:background .15s,color .15s,border-color .15s}
        .side a svg{width:17px;height:17px;flex-shrink:0;opacity:.72}
        .side a:hover{background:rgba(148,163,184,.07);color:#e2e8f0}
        .side a:hover svg{opacity:1}
        .side a.active{background:rgba(79,70,229,.16);color:#fff;border-left-color:var(--p);font-weight:600}
        .side a.active svg{opacity:1;color:#a5b4fc}
        .side .sep{font-size:.67rem;text-transform:uppercase;letter-spacing:.09em;color:#475569;
                   font-weight:700;padding:16px 20px 6px}
        .side .ext{opacity:.6;font-size:.75rem;margin-left:auto}

        /* ---------- MAIN ---------- */
        .main{flex:1;min-width:0;display:flex;flex-direction:column}
        .topbar{background:rgba(255,255,255,.88);backdrop-filter:blur(8px);border-bottom:1px solid var(--border);
                padding:14px 26px;display:flex;justify-content:space-between;align-items:center;
                position:sticky;top:0;z-index:5}
        .topbar h1{font-size:1.17rem;margin:0;font-weight:700;letter-spacing:-.02em}
        .topbar .sub{font-size:.77rem;color:var(--muted);font-weight:500;margin-top:1px}
        .burger{display:none;background:none;border:0;font-size:1.3rem;cursor:pointer;color:#0f172a;padding:0 12px 0 0}
        .content{padding:24px 26px 48px;flex:1}

        /* ---------- KOMPONEN ---------- */
        .card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);
              padding:20px 22px;margin-bottom:18px;box-shadow:var(--shadow-sm)}
        /* Aksen kartu — SEMANTIK, bukan warna acak.
           p = fitur utama/AI · ok = sehat/gratis · warn = perlu perhatian
           bad = masalah kritis · info = data pihak ketiga (Google dsb) */
        .card.a-p{border-left:4px solid var(--p)}
        .card.a-ok{border-left:4px solid var(--g)}
        .card.a-warn{border-left:4px solid var(--warn)}
        .card.a-bad{border-left:4px solid var(--red)}
        .card.a-info{border-left:4px solid var(--info)}
        .card h2,.card h3{letter-spacing:-.02em}
        .card h3{font-size:1.01rem}
        .grid{display:grid;gap:16px}
        .grid-4{grid-template-columns:repeat(auto-fit,minmax(150px,1fr))}
        .stat{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);
              padding:18px 20px;box-shadow:var(--shadow-sm)}
        .stat .n{font-size:1.85rem;font-weight:800;letter-spacing:-.03em}
        .stat .l{color:var(--muted);font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600}
        table{width:100%;border-collapse:collapse;font-size:.89rem}
        th,td{text-align:left;padding:10px;border-bottom:1px solid var(--border);vertical-align:top}
        th{color:var(--muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;font-weight:700}
        tbody tr:hover{background:#fafbfd}
        input,textarea,select{font:inherit;width:100%;padding:9px 12px;border:1px solid var(--border);
                              border-radius:9px;background:#fff;transition:border-color .15s,box-shadow .15s}
        input:focus,textarea:focus,select:focus{outline:0;border-color:var(--p);box-shadow:0 0 0 3px var(--p-soft)}
        input[type=checkbox],input[type=radio]{width:auto}
        input[type=color]{padding:2px}
        textarea{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.84rem;line-height:1.55}
        label{display:block;font-size:.82rem;font-weight:600;margin:10px 0 5px;color:#334155}
        .btn{display:inline-flex;align-items:center;gap:6px;background:var(--p);color:#fff;border:0;
             border-radius:9px;padding:9px 17px;font-weight:600;cursor:pointer;text-decoration:none;
             font-size:.86rem;transition:filter .15s,transform .06s,box-shadow .15s;box-shadow:var(--shadow-sm)}
        .btn:hover{filter:brightness(1.07);box-shadow:var(--shadow)}
        .btn:active{transform:translateY(1px)}
        .btn:disabled{opacity:.5;cursor:not-allowed;filter:none}
        .btn.green{background:var(--g)} .btn.gray{background:#64748b} .btn.red{background:var(--red)}
        .btn.sm{padding:6px 12px;font-size:.78rem}
        .btn.ghost,.btn.light{background:#fff;color:#334155;border:1px solid var(--border);box-shadow:none}
        .btn.ghost:hover,.btn.light:hover{background:#f8fafc;filter:none}
        .pill{display:inline-block;padding:3px 10px;border-radius:999px;font-size:.71rem;font-weight:700}
        .pill.draft{background:#fef3c7;color:#92400e}.pill.published{background:#dcfce7;color:var(--g)}
        .pill.processing{background:#dbeafe;color:#1e40af}.pill.paused{background:#fee2e2;color:var(--red)}
        .pill.completed{background:#dcfce7;color:var(--g)}.pill.queued,.pill.failed{background:#e2e8f0;color:#334155}
        .flash{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;padding:12px 16px;
               border-radius:10px;margin-bottom:16px;font-size:.89rem;font-weight:500}
        .errs{background:#fef2f2;border:1px solid #fecaca;color:var(--red);padding:12px 16px;
              border-radius:10px;margin-bottom:16px;font-size:.89rem}
        .muted{color:var(--muted)}
        .t-bad{color:var(--red)} .t-warn{color:var(--warn)} .t-ok{color:var(--g)}
        .row{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
        .right{margin-left:auto} .mono{font-family:ui-monospace,Menlo,monospace;font-size:.82rem}
        .inline{display:inline}
        code{background:#f1f5f9;padding:1px 6px;border-radius:5px;font-size:.85em;
             font-family:ui-monospace,Menlo,monospace;color:#475569}
        details summary{outline:0}
        .toast-wrap{position:fixed;top:18px;right:18px;z-index:9999;display:grid;gap:12px;max-width:min(360px,calc(100vw - 24px))}
        .toast{position:relative;overflow:hidden;background:rgba(15,23,42,.96);color:#fff;border:1px solid rgba(148,163,184,.18);border-radius:16px;padding:14px 16px 16px;box-shadow:0 18px 50px rgba(15,23,42,.28);backdrop-filter:blur(12px);transform:translateY(-8px);opacity:0;transition:opacity .22s ease,transform .22s ease}
        .toast.show{opacity:1;transform:translateY(0)}
        .toast.success{background:linear-gradient(135deg,rgba(6,78,59,.97),rgba(22,101,52,.97))}
        .toast.error{background:linear-gradient(135deg,rgba(127,29,29,.97),rgba(153,27,27,.97))}
        .toast.info{background:linear-gradient(135deg,rgba(30,64,175,.97),rgba(29,78,216,.97))}
        .toast-title{font-size:.88rem;font-weight:700;letter-spacing:.01em;margin-bottom:4px;display:flex;align-items:center;gap:8px}
        .toast-body{font-size:.84rem;line-height:1.45;color:rgba(255,255,255,.92);padding-right:28px}
        .toast-close{position:absolute;top:8px;right:8px;border:0;background:transparent;color:rgba(255,255,255,.78);font-size:18px;line-height:1;cursor:pointer;padding:4px}
        .toast-bar{position:absolute;left:0;right:0;bottom:0;height:3px;background:rgba(255,255,255,.22)}
        .toast-bar > span{display:block;height:100%;background:rgba(255,255,255,.88);transform-origin:left center;animation:toast-timer var(--dur,4800ms) linear forwards}
        @keyframes toast-timer{from{transform:scaleX(1)}to{transform:scaleX(0)}}

        /* ---------- RESPONSIF ---------- */
        @media(max-width:900px){
            .side{position:fixed;left:0;top:0;z-index:50;transform:translateX(-100%);
                  transition:transform .22s ease;box-shadow:0 0 40px rgba(0,0,0,.3)}
            .side.open{transform:none}
            .burger{display:block}
            .content{padding:18px 16px 40px}
            .topbar{padding:13px 16px}
        }
        @media (max-width: 768px){
            .toast-wrap{top:10px;right:10px;left:10px;max-width:none}
            .toast{border-radius:14px}
        }
    </style>
    @stack('head')
</head>
<body>
<div class="toast-wrap" id="toast-wrap" aria-live="polite" aria-atomic="true"></div>
@php
    $r = request()->route()?->getName();
    $aktif = fn ($p) => str_starts_with($r ?? '', $p) ? 'active' : '';

    // Ikon SVG seragam — menggantikan campuran emoji yang membuat panel terlihat acak.
    $ikon = [
        'home'   => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>',
        'rocket' => '<path d="M5 13c-1.5 1.5-2 5-2 5s3.5-.5 5-2"/><path d="M14.5 4.5C17 2 21 3 21 3s1 4-1.5 6.5L13 16l-5-5z"/><circle cx="15" cy="9" r="1.2"/>',
        'layers' => '<path d="M12 2l9 5-9 5-9-5z"/><path d="M3 12l9 5 9-5"/><path d="M3 17l9 5 9-5"/>',
        'chat'   => '<path d="M4 4h16v12H7l-3 3z"/>',
        'file'   => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/>',
        'tag'    => '<path d="M20 12l-8 8-9-9V3h8z"/><circle cx="7.5" cy="7.5" r="1.2"/>',
        'brain'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'upload' => '<path d="M12 15V3"/><path d="M8 7l4-4 4 4"/><path d="M4 15v4a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-4"/>',
        'grid'   => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'wa'     => '<path d="M21 11.5a8.5 8.5 0 0 1-12.7 7.4L3 20l1.2-5.1A8.5 8.5 0 1 1 21 11.5z"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
        'gear'   => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M19.1 4.9L17 7M7 17l-2.1 2.1"/>',
        'map'    => '<path d="M9 4L3 6v14l6-2 6 2 6-2V4l-6 2z"/><path d="M9 4v14M15 6v14"/>',
    ];
    $svg = fn ($k) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . ($ikon[$k] ?? '') . '</svg>';
@endphp
<div class="wrap">
    <aside class="side" id="side">
        <div class="brand">
            @if($__engineLogo)
                <img src="{{ $__engineLogo }}" alt="{{ $__engine }}" style="width:26px;height:26px;object-fit:contain;border-radius:6px;flex-shrink:0">
            @else
                <span class="dot">⚡</span>
            @endif
            {{ $__engine }}
        </div>

        <a href="{{ route('admin.dashboard') }}" class="{{ $r==='admin.dashboard'?'active':'' }}">{!! $svg('home') !!} Dashboard</a>

        <div class="sep">Mulai</div>
        <a href="{{ route('admin.quickstart.index') }}" class="{{ $aktif('admin.quickstart') }}">{!! $svg('rocket') !!} Mulai Cepat</a>
        <a href="{{ route('admin.assistant.index') }}" class="{{ $aktif('admin.assistant') }}">{!! $svg('brain') !!} Asisten SEO</a>

        <div class="sep">Konten</div>
        <a href="{{ route('admin.content.index') }}" class="{{ $aktif('admin.content') }}">{!! $svg('layers') !!} Variasi Konten</a>
        <a href="{{ route('admin.faqs.index') }}" class="{{ $aktif('admin.faqs') }}">{!! $svg('chat') !!} FAQ</a>
        <a href="{{ route('admin.templates.index') }}" class="{{ $aktif('admin.templates') }}">{!! $svg('grid') !!} Template</a>
        <a href="{{ route('admin.services.index') }}" class="{{ $aktif('admin.services') }}">{!! $svg('tag') !!} Layanan &amp; Harga</a>
        <a href="{{ route('admin.keywords.index') }}" class="{{ $aktif('admin.keywords') }}">{!! $svg('tag') !!} Keyword AI</a>

        <div class="sep">Halaman</div>
        <a href="{{ route('admin.imports.index') }}" class="{{ $aktif('admin.imports') }}">{!! $svg('upload') !!} Import Data</a>
        <a href="{{ route('admin.pages.index') }}" class="{{ $aktif('admin.pages') }}">{!! $svg('file') !!} Halaman pSEO</a>
        <a href="{{ route('admin.sitepages.index') }}" class="{{ $aktif('admin.sitepages') }}">{!! $svg('map') !!} Halaman Statis</a>

        <div class="sep">Performa</div>
        <a href="{{ route('admin.leads.index') }}" class="{{ $aktif('admin.leads') }}">{!! $svg('wa') !!} Lead WhatsApp</a>
        <a href="{{ route('admin.indexing.index') }}" class="{{ $aktif('admin.indexing') }}">{!! $svg('search') !!} Indexing &amp; Peringkat</a>

        <div class="sep">Sistem</div>
        <a href="{{ route('admin.settings.edit') }}" class="{{ $aktif('admin.settings') }}">{!! $svg('gear') !!} Pengaturan</a>
        <a href="{{ url('/sitemap.xml') }}" target="_blank" rel="noopener">{!! $svg('map') !!} Sitemap <span class="ext">&#8599;</span></a>
    </aside>

    <div class="main">
        <div class="topbar">
            <div style="display:flex;align-items:center">
                <button class="burger" onclick="document.getElementById('side').classList.toggle('open')" aria-label="Menu">&#9776;</button>
                <div>
                    <h1>@yield('title', 'Admin')</h1>
                    <div class="sub">{{ \App\Models\Setting::get('brand_name') ?: 'Nama brand belum diisi' }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="btn ghost sm">Keluar</button>
            </form>
        </div>
        <div class="content">
            @if(session('status'))<div class="flash">{{ session('status') }}</div>@endif
            @if($errors->any())
                <div class="errs">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
            @endif
            @yield('content')
        </div>
    </div>
</div>
@stack('scripts')
<script>
(() => {
    const wrap = document.getElementById('toast-wrap');
    if(!wrap) return;

    function icon(type){
        if(type === 'success') return 'OK';
        if(type === 'error') return '!';
        return 'i';
    }

    function title(type){
        if(type === 'success') return 'Berhasil';
        if(type === 'error') return 'Perlu Dicek';
        return 'Informasi';
    }

    function showToast(opts){
        const type = opts?.type || 'info';
        const message = (opts?.message || '').trim();
        if(!message) return;

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        const duration = Math.max(2500, Number(opts?.duration || 4800));

        toast.innerHTML = `
            <button class="toast-close" type="button" aria-label="Tutup">x</button>
            <div class="toast-title"><span>${icon(type)}</span><span>${opts?.title || title(type)}</span></div>
            <div class="toast-body"></div>
            <div class="toast-bar" style="--dur:${duration}ms"><span></span></div>
        `;
        toast.querySelector('.toast-body').textContent = message;
        wrap.prepend(toast);

        requestAnimationFrame(() => {
            toast.classList.add('show');
        });

        const close = () => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 220);
        };

        toast.querySelector('.toast-close')?.addEventListener('click', close);
        setTimeout(close, duration);
    }

    window.CeguAdminToast = showToast;
    window.addEventListener('cegu:toast', (event) => showToast(event.detail || {}));

    @if(session('status'))
        showToast({type:'success', message:@json(session('status')), duration:4200});
    @endif
    @if($errors->any())
        showToast({type:'error', message:@json($errors->first()), duration:6000});
    @endif
})();
</script>
</body>
</html>
