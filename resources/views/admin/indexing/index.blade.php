@extends('admin.layout')
@section('title', 'Indexing & Peringkat')

@section('content')
@php
    $r = $ringkasan;
    $totalDicek = max(1, $r['terindeks'] + $r['belum_terindeks']);
    $persen = (int) round($r['terindeks'] / $totalDicek * 100);
@endphp

<div class="card a-info">
    <h2 style="margin:0 0 4px">Indexing &amp; Peringkat Google</h2>
    <p class="muted" style="margin:0">Berapa halaman yang sudah masuk indeks Google, berapa yang belum, dan berapa yang benar-benar muncul di hasil pencarian.</p>

    @error('gsc')<p style="color:var(--red);margin:10px 0 0">{{ $message }}</p>@enderror
    @if(! $configured)
        <p style="color:var(--red);margin:10px 0 0">Search Console belum terhubung. Isi <code>GSC_CREDENTIALS</code> &amp; <code>GSC_SITE_URL</code> di <code>.env</code>, lalu <code>php artisan config:clear</code>.</p>
    @endif
</div>

{{-- ============ ESTIMASI TERINDEKS (TANPA KUOTA) ============ --}}
@if($estimasi && empty($estimasi['error']))
<div class="card a-ok">
    <h3 style="margin-top:0">Terindeks menurut data pencarian <span class="muted" style="font-weight:400;font-size:.85rem">({{ $estimasi['hari'] }} hari)</span></h3>
    <div style="display:flex;gap:26px;flex-wrap:wrap;align-items:baseline">
        <div><div style="font-size:2rem;font-weight:800;color:var(--g)">{{ number_format($estimasi['terindeks_minimal']) }}</div><div class="muted" style="font-size:.8rem">halaman minimal terindeks</div></div>
        <p class="muted" style="margin:0;flex:1;min-width:260px;font-size:.88rem">
            Halaman yang pernah <strong>muncul di hasil pencarian</strong> pasti sudah terindeks. Angka ini <strong>tidak memakai kuota inspeksi</strong>, jadi bisa memantau ratusan ribu halaman sekaligus.<br>
            Ini <strong>batas bawah</strong> — halaman yang terindeks tapi belum pernah tampil tidak terhitung.
        </p>
    </div>
    @if($estimasi['tercapai_batas'])
        <p class="muted" style="margin:8px 0 0;font-size:.85rem">Catatan: batas pengambilan data tercapai — jumlah sebenarnya mungkin lebih besar.</p>
    @endif

    <form method="POST" action="{{ route('admin.indexing.sync') }}" style="margin-top:12px">
        @csrf
        <button class="btn green">🔄 Tandai Terindeks dari Data Pencarian</button>
        <span class="muted" style="font-size:.85rem;margin-left:8px">Tidak memakai kuota inspeksi. Sisanya otomatis masuk daftar "belum terindeks" di bawah.</span>
    </form>
    @if(($sync['status'] ?? '') !== 'idle')
        <p class="muted" style="margin:8px 0 0;font-size:.85rem">{{ $sync['message'] ?? '' }}</p>
    @endif
</div>
@endif

{{-- ============ STATUS INDEXING ============ --}}
<div class="card">
    <div class="row">
        <h3 style="margin:0">Status Indexing</h3>
        <div class="right muted" style="font-size:.85rem">
            Kuota hari ini: <strong>{{ number_format($r['kuota_terpakai']) }}</strong> / {{ number_format(\App\Services\SearchConsole\UrlInspectionService::DAILY_QUOTA) }}
            (sisa {{ number_format($r['kuota_sisa']) }})
        </div>
    </div>

    <div style="display:flex;gap:26px;flex-wrap:wrap;margin:14px 0">
        <div><div style="font-size:1.7rem;font-weight:700">{{ number_format($r['published']) }}</div><div class="muted" style="font-size:.8rem">halaman published</div></div>
        <div><div style="font-size:1.7rem;font-weight:700;color:var(--g)">{{ number_format($r['terindeks']) }}</div><div class="muted" style="font-size:.8rem">terindeks Google</div></div>
        <div><div style="font-size:1.7rem;font-weight:700;color:var(--red)">{{ number_format($r['belum_terindeks']) }}</div><div class="muted" style="font-size:.8rem">belum terindeks</div></div>
        <div><div style="font-size:1.7rem;font-weight:700;color:#888">{{ number_format($r['belum_dicek']) }}</div><div class="muted" style="font-size:.8rem">belum diperiksa</div></div>
    </div>

    @if($r['terindeks'] + $r['belum_terindeks'] > 0)
        <div style="height:12px;background:var(--border);border-radius:6px;overflow:hidden;display:flex">
            <div style="width:{{ $persen }}%;background:var(--g)"></div>
            <div style="width:{{ 100 - $persen }}%;background:var(--red)"></div>
        </div>
        <p class="muted" style="margin:6px 0 0;font-size:.85rem">{{ $persen }}% dari halaman yang diperiksa sudah terindeks.</p>
    @endif

    <form method="POST" action="{{ route('admin.indexing.inspect') }}" class="row" style="gap:10px;align-items:flex-end;margin-top:16px;flex-wrap:wrap">
        @csrf
        <div style="min-width:150px">
            <label style="margin:0">Periksa berapa halaman?</label>
            <input type="number" name="jumlah" value="100" min="1" max="{{ max(1, $r['kuota_sisa']) }}">
        </div>
        <label style="display:flex;align-items:center;gap:6px;margin:0 0 8px">
            <input type="checkbox" name="sampling" value="1"> Mode sampel acak
        </label>
        <button class="btn" {{ $configured && $r['kuota_sisa'] > 0 ? '' : 'disabled' }}>🔍 Periksa Status ke Google</button>
        <span class="muted" style="font-size:.85rem">Normal: mendahulukan yang belum pernah diperiksa. <strong>Sampel acak</strong>: untuk situs besar — periksa 300–500 halaman acak untuk memperkirakan kondisi keseluruhan tanpa menghabiskan kuota. Butuh worker antrian.</span>
    </form>

    <div id="prog" style="margin-top:12px;display:none">
        <div style="height:8px;background:var(--border);border-radius:5px;overflow:hidden">
            <div id="bar" style="height:100%;width:0;background:var(--info);transition:width .4s"></div>
        </div>
        <p id="pmsg" class="muted" style="margin:6px 0 0;font-size:.85rem"></p>
    </div>
</div>

{{-- ============ ALASAN BELUM TERINDEKS ============ --}}
@if(!empty($r['alasan']))
<div class="card">
    <h3 style="margin-top:0">Kenapa belum terindeks?</h3>
    <table style="width:100%;border-collapse:collapse;font-size:.9rem">
        <thead><tr style="text-align:left;border-bottom:1px solid var(--border)">
            <th style="padding:6px 0">Status dari Google</th><th style="width:90px;text-align:right">Jumlah</th>
        </tr></thead>
        <tbody>
        @foreach($r['alasan'] as $state => $jml)
            <tr style="border-bottom:1px solid var(--border)">
                <td style="padding:6px 0">{{ $state }}</td>
                <td style="text-align:right;font-weight:600">{{ number_format($jml) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <p class="muted" style="margin:10px 0 0;font-size:.85rem">
        <strong>"Discovered – currently not indexed"</strong> berarti Google tahu halamannya tapi memilih belum mengindeks — biasanya sinyal halaman dinilai kurang bernilai. Perkaya data lokal dan kurangi laju publish.
    </p>
</div>
@endif

{{-- ============ PERINGKAT ============ --}}
@if($ranking && empty($ranking['error']))
<div class="card">
    <h3 style="margin-top:0">Muncul di Peringkat Google <span class="muted" style="font-weight:400;font-size:.85rem">(28 hari)</span></h3>
    <div style="display:flex;gap:26px;flex-wrap:wrap;margin:12px 0">
        <div><div style="font-size:1.7rem;font-weight:700">{{ number_format($ranking['total_tampil']) }}</div><div class="muted" style="font-size:.8rem">halaman tampil</div></div>
        <div><div style="font-size:1.7rem;font-weight:700;color:var(--g)">{{ number_format($ranking['top3']) }}</div><div class="muted" style="font-size:.8rem">posisi 1–3</div></div>
        <div><div style="font-size:1.7rem;font-weight:700">{{ number_format($ranking['top10']) }}</div><div class="muted" style="font-size:.8rem">posisi 4–10</div></div>
        <div><div style="font-size:1.7rem;font-weight:700">{{ number_format($ranking['top20']) }}</div><div class="muted" style="font-size:.8rem">posisi 11–20</div></div>
        <div><div style="font-size:1.7rem;font-weight:700;color:#888">{{ number_format($ranking['sisanya']) }}</div><div class="muted" style="font-size:.8rem">posisi 20+</div></div>
        <div><div style="font-size:1.7rem;font-weight:700">{{ number_format($ranking['ada_klik']) }}</div><div class="muted" style="font-size:.8rem">dapat klik</div></div>
    </div>

    @if(!empty($ranking['halaman']))
    <p class="muted" style="margin:0 0 6px">Halaman dengan impresi tertinggi:</p>
    <table style="width:100%;border-collapse:collapse;font-size:.88rem">
        <thead><tr style="text-align:left;border-bottom:1px solid var(--border)">
            <th style="padding:6px 0">Halaman</th><th style="width:70px;text-align:right">Klik</th>
            <th style="width:80px;text-align:right">Impresi</th><th style="width:70px;text-align:right">Posisi</th>
        </tr></thead>
        <tbody>
        @foreach($ranking['halaman'] as $h)
            <tr style="border-bottom:1px solid var(--border)">
                <td style="padding:6px 0"><a href="{{ $h['url'] }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit(parse_url($h['url'], PHP_URL_PATH) ?: $h['url'], 60) }}</a></td>
                <td style="text-align:right">{{ number_format($h['klik']) }}</td>
                <td style="text-align:right">{{ number_format($h['impresi']) }}</td>
                <td style="text-align:right">{{ $h['posisi'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @endif
</div>
@elseif($ranking && !empty($ranking['error']))
<div class="card"><p class="muted" style="margin:0">Data peringkat belum bisa diambil: {{ $ranking['error'] }}</p></div>
@endif

{{-- ============ DAFTAR KERJA: BELUM TERINDEKS ============ --}}
@php $dimintaHariIni = \App\Models\PageIndexStatus::whereDate('requested_at', now()->toDateString())->count(); @endphp
<div class="card a-bad">
    <div class="row">
        <h3 style="margin:0">Belum terindeks — {{ number_format($belum->total()) }} halaman</h3>
        <div class="right muted" style="font-size:.85rem">
            Diminta hari ini: <strong>{{ $dimintaHariIni }}</strong> / ~12
        </div>
    </div>

    <p class="muted" style="margin:8px 0 12px;font-size:.88rem">
        Google <strong>tidak menyediakan API</strong> untuk meminta pengindeksan halaman umum, jadi permintaan dilakukan manual di Search Console — kuotanya sekitar <strong>10–12 URL per hari</strong>.
        Klik <em>Minta Index ↗</em> (membuka GSC, tekan <em>Request Indexing</em> di sana), lalu tekan <em>Tandai</em> agar tidak terulang besok.
    </p>

    @if($belum->count() === 0)
        <p class="muted" style="margin:0">✅ Semua halaman published sudah terbukti terindeks.</p>
    @else
    <table style="width:100%;border-collapse:collapse;font-size:.88rem">
        <thead><tr style="text-align:left;border-bottom:1px solid var(--border)">
            <th style="padding:6px 0">Halaman</th><th style="width:180px">Status</th><th style="width:230px"></th>
        </tr></thead>
        <tbody>
        @foreach($belum as $b)
            @php $url = url('/' . ltrim($b->path, '/')); @endphp
            <tr style="border-bottom:1px solid var(--border)" id="row-{{ $b->id }}">
                <td style="padding:6px 0"><a href="{{ $url }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($b->path, 48) }}</a></td>
                <td class="muted">
                    @if($b->requested_at)
                        <span style="color:var(--warn)">Diminta {{ \Illuminate\Support\Carbon::parse($b->requested_at)->diffForHumans() }}</span>
                    @elseif($b->coverage_state)
                        {{ \Illuminate\Support\Str::limit($b->coverage_state, 28) }}
                    @else
                        <span style="opacity:.6">belum diperiksa</span>
                    @endif
                </td>
                <td style="text-align:right;white-space:nowrap">
                    <a class="btn sm light" href="{{ $gscBaseUrl . rawurlencode($url) }}" target="_blank" rel="noopener"
                       onclick="tandai({{ $b->id }}, this)">Minta Index ↗</a>
                    <button type="button" class="btn sm" onclick="cek({{ $b->id }}, this)">Cek</button>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div style="margin-top:12px">{{ $belum->links() }}</div>
    @endif
</div>

<script>
var CSRF="{{ csrf_token() }}", URL_ONE="{{ route('admin.indexing.one') }}",
    URL_PROG="{{ route('admin.indexing.progress') }}", URL_REQ="{{ route('admin.indexing.requested') }}";

// Saat tombol "Minta Index" diklik (membuka GSC di tab baru), sekaligus catat
// bahwa URL ini sudah diminta — supaya tidak terulang besok.
function tandai(id, el){
    fetch(URL_REQ,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},body:JSON.stringify({page_id:id})})
      .then(function(r){return r.json();})
      .then(function(d){ if(d.ok){ el.textContent='✔ Diminta'; el.classList.remove('light'); } })
      .catch(function(){});
}

function cek(id, btn){
    btn.disabled=true; var lama=btn.textContent; btn.textContent='…';
    fetch(URL_ONE,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},body:JSON.stringify({page_id:id})})
      .then(function(r){return r.json();})
      .then(function(d){
          if(!d.ok){ alert(d.pesan||'Gagal'); btn.disabled=false; btn.textContent=lama; return; }
          btn.textContent = d.terindeks ? '✅ Terindeks' : '❌ ' + (d.coverage||'Belum');
      })
      .catch(function(){ btn.disabled=false; btn.textContent=lama; });
}

(function(){
    var box=document.getElementById('prog'), bar=document.getElementById('bar'), msg=document.getElementById('pmsg'), t=null;
    function poll(){
        fetch(URL_PROG,{headers:{'Accept':'application/json'}}).then(function(r){return r.json();}).then(function(d){
            if(d.status==='idle') return;
            box.style.display='block';
            var pct = d.total>0 ? Math.round(d.done/d.total*100) : 0;
            bar.style.width=pct+'%';
            if(d.status==='error') bar.style.background='#c0392b';
            msg.textContent=(d.message||'')+' · sisa kuota: '+(d.kuota_sisa||0);
            if(d.status==='done'||d.status==='error'){ clearInterval(t); setTimeout(function(){location.reload();},3000); }
        }).catch(function(){});
    }
    t=setInterval(poll,3000); poll();
})();
</script>
@endsection
