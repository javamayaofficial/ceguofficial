@extends('admin.layout')
@section('title', 'Dashboard Generate')

@section('content')
    {{-- ============ ONBOARDING TERPANDU (tampil sampai semua langkah selesai) ============ --}}
    @if(! $onboarding['complete'])
    <div class="card a-p">
        <div class="row">
            <h2 style="margin:0">Mulai dari sini — {{ $onboarding['done_count'] }}/{{ $onboarding['total'] }} langkah selesai</h2>
        </div>
        <div style="margin-top:12px;display:flex;flex-direction:column;gap:10px">
            @foreach($onboarding['steps'] as $step)
                <div style="display:flex;gap:10px;align-items:flex-start;opacity:{{ $step['done'] ? '.55' : '1' }}">
                    <span style="font-size:1.1rem;line-height:1.3">{{ $step['done'] ? '✅' : '⬜' }}</span>
                    <div>
                        <strong>{{ $step['title'] }}</strong>
                        @if(!$step['done'] && $step['url'])
                            — <a href="{{ $step['url'] }}" @if(str_starts_with($step['url'],'http') && !str_contains($step['url'], request()->getHost())) target="_blank" rel="noopener" @endif>kerjakan →</a>
                        @endif
                        <div class="muted" style="font-size:.85rem">{{ $step['hint'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ============ GOOGLE SEARCH CONSOLE (opsional) ============ --}}
    @if($gsc)
    <div class="card a-info">
        <h2 style="margin:0 0 10px">Search Console <span class="muted" style="font-weight:400;font-size:.9rem">(28 hari)</span></h2>
        @if(isset($gsc['error']))
            <p class="muted" style="margin:0">Belum bisa mengambil data: {{ $gsc['error'] }}</p>
        @else
            <div style="display:flex;gap:26px;flex-wrap:wrap">
                <div><div style="font-size:1.6rem;font-weight:700">{{ number_format($gsc['clicks']) }}</div><div class="muted" style="font-size:.8rem">klik</div></div>
                <div><div style="font-size:1.6rem;font-weight:700">{{ number_format($gsc['impressions']) }}</div><div class="muted" style="font-size:.8rem">impresi</div></div>
                <div><div style="font-size:1.6rem;font-weight:700">{{ $gsc['ctr'] }}%</div><div class="muted" style="font-size:.8rem">CTR</div></div>
                <div><div style="font-size:1.6rem;font-weight:700">{{ $gsc['position'] }}</div><div class="muted" style="font-size:.8rem">posisi rata-rata</div></div>
            </div>
        @endif
    </div>
    @endif

    {{-- ============ PELACAKAN LEAD (KLIK WHATSAPP) ============ --}}
    <div class="card a-ok">
        <div class="row">
            <h2 style="margin:0">Klik WhatsApp <span class="muted" style="font-weight:400;font-size:.9rem">(30 hari terakhir)</span></h2>
            <div class="right" style="display:flex;gap:18px;align-items:baseline">
                <div style="text-align:right"><div style="font-size:1.6rem;font-weight:700">{{ number_format($leads['total']) }}</div><div class="muted" style="font-size:.8rem">total klik</div></div>
                <div style="text-align:right"><div style="font-size:1.6rem;font-weight:700">{{ number_format($leads['today']) }}</div><div class="muted" style="font-size:.8rem">hari ini</div></div>
            </div>
        </div>
        @if($leads['total'] === 0)
            <p class="muted" style="margin:10px 0 0">Belum ada klik tercatat. Pastikan halaman sudah dipublish dan sudah dikunjungi. Data akan muncul otomatis saat pengunjung menekan tombol WhatsApp.</p>
        @else
            <p class="muted" style="margin:10px 0 6px">Halaman paling menghasilkan chat — gandakan pola yang menang:</p>
            <table style="width:100%;border-collapse:collapse;font-size:.9rem">
                <thead><tr style="text-align:left;border-bottom:1px solid var(--border)"><th style="padding:6px 0">Halaman</th><th style="padding:6px 0;width:90px;text-align:right">Klik</th></tr></thead>
                <tbody>
                @foreach($leads['top'] as $row)
                    <tr style="border-bottom:1px solid var(--border)">
                        <td style="padding:6px 0"><a href="{{ url($row->page_path) }}" target="_blank" rel="noopener">{{ $row->page_path }}</a></td>
                        <td style="padding:6px 0;text-align:right;font-weight:600">{{ number_format($row->total) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- ============ KESEHATAN STOK KONTEN ============ --}}
    <div class="card">
        <div class="row">
            <h2 style="margin:0">
                Kesehatan Stok Konten
                @if($health['all_ok'])
                    <span class="pill completed" style="margin-left:8px">SIAP GENERATE MASSAL</span>
                @else
                    <span class="pill failed" style="margin-left:8px">SKOR {{ $health['score'] }}% — TAMBAH STOK DULU</span>
                @endif
            </h2>
            <a class="btn sm right" href="{{ route('admin.content.index') }}">Tambah Variasi</a>
        </div>
        @if(! $health['all_ok'])
            <p class="muted" style="margin:8px 0 4px">⚠️ Stok kalimat di bawah target = halaman terlihat kembar di mata Google → banyak ditolak indeks. Penuhi target sebelum menayangkan halaman dalam jumlah besar.</p>
        @endif
        <div style="margin-top:12px;display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px">
            @foreach($health['sections'] as $sec)
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:.85rem">
                        <span>{{ $sec['label'] }}</span>
                        <strong style="color:{{ $sec['ok'] ? '#1a9e55' : '#c0392b' }}">{{ $sec['count'] }}/{{ $sec['target'] }}</strong>
                    </div>
                    <div style="background:#e8ebf2;border-radius:6px;height:8px;overflow:hidden;margin-top:4px">
                        <div style="width:{{ $sec['percent'] }}%;height:100%;background:{{ $sec['ok'] ? '#1a9e55' : '#f0a03a' }}"></div>
                    </div>
                </div>
            @endforeach
            <div>
                <div style="display:flex;justify-content:space-between;font-size:.85rem">
                    <span>FAQ aktif</span>
                    <strong style="color:{{ $health['faq']['ok'] ? '#1a9e55' : '#c0392b' }}">{{ $health['faq']['count'] }}/{{ $health['faq']['target'] }}</strong>
                </div>
                <div style="background:#e8ebf2;border-radius:6px;height:8px;overflow:hidden;margin-top:4px">
                    <div style="width:{{ $health['faq']['percent'] }}%;height:100%;background:{{ $health['faq']['ok'] ? '#1a9e55' : '#f0a03a' }}"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-4" style="margin-bottom:18px">
        <div class="stat"><div class="n">{{ number_format($stats['total']) }}</div><div class="l">Total Halaman</div></div>
        <div class="stat"><div class="n">{{ number_format($stats['draft']) }}</div><div class="l">Draft</div></div>
        <div class="stat"><div class="n">{{ number_format($stats['published']) }}</div><div class="l">Published</div></div>
        <div class="stat"><div class="n">{{ number_format($generate['failed']) }}</div><div class="l">Gagal</div></div>
    </div>

    <div class="card">
        <h2 style="margin-top:0">Progres Generate</h2>
        <div class="grid grid-4">
            <div><div class="muted">Baris CSV</div><strong>{{ number_format($generate['total_rows']) }}</strong></div>
            <div><div class="muted">Sedang diproses</div><strong>{{ number_format($generate['processing']) }}</strong></div>
            <div><div class="muted">Selesai diproses</div><strong>{{ number_format($generate['done']) }}</strong></div>
            <div><div class="muted">Publish Queue</div><span class="pill {{ $publishState }}">{{ $publishState }}</span></div>
        </div>
    </div>

    <div class="card">
        <div class="row">
            <h2 style="margin:0">Batch Import Terakhir</h2>
            <a class="btn sm right" href="{{ route('admin.imports.index') }}">Kelola Import</a>
        </div>
        <table style="margin-top:12px">
            <thead><tr><th>File</th><th>Status</th><th>Progress</th><th>Generated</th><th>Gagal</th></tr></thead>
            <tbody>
            @forelse($batches as $b)
                <tr>
                    <td class="mono">{{ $b->original_filename }}</td>
                    <td><span class="pill {{ $b->status }}">{{ $b->status }}</span></td>
                    <td>{{ number_format($b->processed_rows) }} / {{ number_format($b->total_rows) }}</td>
                    <td>{{ number_format($b->generated_count) }}</td>
                    <td>{{ number_format($b->failed_count) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Belum ada import. <a href="{{ route('admin.imports.index') }}">Upload CSV pertama →</a></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
