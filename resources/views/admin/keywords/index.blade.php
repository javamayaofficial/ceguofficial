@extends('admin.layout')
@section('title', 'Keyword AI')

@section('content')
    <div class="card a-p">
        <h3 style="margin-top:0">Generator Keyword Longtail (AI)</h3>
        <p class="muted" style="margin-top:0">Hasilkan variasi keyword <strong>layanan</strong> untuk niche Anda. Keyword ini jadi dimensi yang <strong>dikalikan otomatis</strong> dengan lokasi resmi (kota/kecamatan/kelurahan) di Import CSV → jutaan halaman. Keyword sengaja <strong>tanpa nama kota</strong> (kota ditambahkan saat cross-join).</p>

        @error('ai')<p style="color:var(--red)">{{ $message }}</p>@enderror
        @if(! $configured)
            <p style="color:var(--red)">Kunci API AI belum diatur. Isi <code>AI_DRIVER</code>, <code>AI_API_KEY</code>, <code>AI_MODEL</code> di <code>.env</code>.</p>
        @endif

        <form method="POST" action="{{ route('admin.keywords.generate') }}" class="row" style="gap:12px;align-items:flex-end;flex-wrap:wrap">
            @csrf
            <div style="flex:2;min-width:240px">
                <label style="margin:0">Niche / jenis usaha <span style="color:var(--red)">*</span></label>
                <input name="business" required value="{{ $business ?? old('business') }}" placeholder="mis. les privat, jasa service AC, herbal, agen properti">
            </div>
            <div style="flex:2;min-width:240px">
                <label style="margin:0">Kata kunci awal (opsional)</label>
                <input name="seeds" value="{{ old('seeds') }}" placeholder="mis. matematika, fisika, calistung">
            </div>
            <div style="min-width:120px">
                <label style="margin:0">Jumlah</label>
                <input type="number" name="count" value="{{ old('count', 100) }}" min="10" max="400">
            </div>
            <button class="btn">✨ Generate</button>
        </form>
    </div>

    @if(!is_null($keywords))
    <div class="card">
        <div class="row">
            <h3 style="margin:0">Hasil — {{ count($keywords) }} keyword</h3>
            <div class="right" style="display:flex;gap:8px">
                <button type="button" class="btn sm" onclick="copyKw()">📋 Salin</button>
                <button type="button" class="btn sm" onclick="downloadKw()">⬇️ Download CSV</button>
            </div>
        </div>
        @if(count($keywords) === 0)
            <p class="muted">Tidak ada keyword dihasilkan. Coba lagi atau perjelas niche.</p>
        @else
            <p class="muted" style="margin:8px 0 4px">Salin daftar ini ke kolom <strong>"Daftar Layanan"</strong> pada halaman Import CSV (satu keyword per baris):</p>
            <textarea id="kwbox" rows="14" style="width:100%;font-family:monospace;font-size:.85rem">{{ implode("\n", $keywords) }}</textarea>
            <div style="margin-top:10px"><a class="btn" href="{{ route('admin.imports.index') }}">Lanjut ke Import CSV →</a></div>
        @endif
    </div>

    <script>
    function copyKw(){ var t=document.getElementById('kwbox'); t.select(); document.execCommand('copy'); }
    function downloadKw(){
        var lines=document.getElementById('kwbox').value.split('\n').filter(Boolean);
        var csv='layanan\n'+lines.map(function(l){return '"'+l.replace(/"/g,'""')+'"';}).join('\n');
        var blob=new Blob([csv],{type:'text/csv'});
        var a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download='keyword-layanan.csv'; a.click();
    }
    </script>
    @endif
@endsection
