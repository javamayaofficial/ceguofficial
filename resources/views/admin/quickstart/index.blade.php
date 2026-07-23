@extends('admin.layout')
@section('title', 'Mulai Cepat')

@section('content')
@php
    $skor = $health['score'] ?? 0;
    $warna = $skor >= 100 ? '#1a9e55' : ($skor >= 50 ? '#e0a800' : '#c0392b');
@endphp

<div class="card a-p">
    <div class="row">
        <div>
            <h2 style="margin:0">Mulai Cepat</h2>
            <p class="muted" style="margin:4px 0 0">Isi kata kunci yang Anda bidik — AI (dibimbing otak MD) akan menulis seluruh variasi konten, FAQ, dan menyiapkan halaman sampai indikator hijau.</p>
        </div>
        <div class="right" style="text-align:center">
            <div style="font-size:2rem;font-weight:800;color:{{ $warna }}">{{ $skor }}</div>
            <div class="muted" style="font-size:.75rem">stok konten</div>
        </div>
    </div>

    @error('ai')<p style="color:var(--red);margin:10px 0 0">{{ $message }}</p>@enderror
    @if(! $configured)
        <p style="color:var(--red);margin:10px 0 0">Kunci API AI belum diatur. Isi <code>AI_DRIVER</code>, <code>AI_API_KEY</code>, <code>AI_MODEL</code> di <code>.env</code>, lalu <code>php artisan config:clear</code>.</p>
    @endif
    @if(! $waTerisi)
        <p style="color:var(--red);margin:10px 0 0">⚠️ Nomor WhatsApp belum diisi di Pengaturan. Halaman tidak akan menghasilkan chat sampai ini diperbaiki.</p>
    @endif
</div>

<form method="POST" action="{{ route('admin.quickstart.run') }}">
    @csrf

    <div class="card">
        <h3 style="margin-top:0">1. Kata kunci yang dibidik</h3>
        <label>Kata kunci utama <span style="color:var(--red)">*</span></label>
        <input name="keyword" required value="{{ old('keyword') }}" placeholder="mis. Les Privat Matematika">
        <p class="muted" style="margin:4px 0 0;font-size:.85rem">2–4 kata, <strong>tanpa nama kota</strong> (kota ditambahkan otomatis).</p>

        <label style="margin-top:12px">Kata kunci tambahan (opsional, satu per baris)</label>
        <textarea name="keyword_lain" rows="3" placeholder="Les Privat Matematika SD&#10;Les Privat Matematika SMP">{{ old('keyword_lain') }}</textarea>
        <p class="muted" style="margin:4px 0 0;font-size:.85rem">Ingat pengalinya: tiap kata kunci × jumlah lokasi = jumlah halaman.</p>

        <div class="row" style="gap:12px;flex-wrap:wrap;margin-top:12px">
            <div style="flex:1;min-width:220px">
                <label>Jenis usaha (opsional)</label>
                <input name="business" value="{{ old('business') }}" placeholder="mis. bimbingan belajar privat">
            </div>
            <div style="flex:1;min-width:180px">
                <label>Gaya bahasa (opsional)</label>
                <input name="tone" value="{{ old('tone') }}" placeholder="ramah &amp; meyakinkan">
            </div>
        </div>
    </div>

    <div class="card">
        <h3 style="margin-top:0">2. Wilayah</h3>
        <p class="muted" style="margin-top:0">Diambil dari data wilayah resmi Indonesia — nama daerah dijamin akurat, bukan karangan AI.</p>
        <div class="row" style="gap:12px;flex-wrap:wrap">
            <div style="flex:1;min-width:200px">
                <label>Provinsi</label>
                <select id="prov" onchange="filterCity()">
                    <option value="">— pilih provinsi —</option>
                    @foreach($provinces as $kode => $nama)
                        <option value="{{ $kode }}">{{ $nama }}</option>
                    @endforeach
                </select>
            </div>
            <div style="flex:1;min-width:200px">
                <label>Kota / Kabupaten</label>
                <select name="city_kode" id="city">
                    <option value="">— pilih provinsi dulu —</option>
                </select>
            </div>
            <div style="min-width:160px">
                <label>Tingkat halaman</label>
                <select name="level">
                    <option value="kelurahan">Kelurahan (terbanyak)</option>
                    <option value="kecamatan">Kecamatan (lebih sedikit)</option>
                </select>
            </div>
        </div>
        <p class="muted" style="margin:10px 0 0;font-size:.85rem">Kosongkan wilayah bila hanya ingin mengisi stok konten tanpa membuat halaman.</p>
    </div>

    <div class="card">
        <h3 style="margin-top:0">3. Data lokal <span class="muted" style="font-weight:400;font-size:.85rem">(sangat disarankan)</span></h3>
        <p class="muted" style="margin-top:0">Inilah pembeda utama antar halaman — dan pertahanan terbaik agar tidak dinilai konten massal oleh Google. AI tidak tahu data ini, hanya Anda.</p>
        <div class="row" style="gap:12px;flex-wrap:wrap">
            <div style="flex:1;min-width:170px">
                <label>Harga mulai dari</label>
                <input name="harga" value="{{ old('harga') }}" placeholder="mis. Rp100.000/sesi">
            </div>
            <div style="flex:1;min-width:170px">
                <label>Jam operasional</label>
                <input name="jam_operasional" value="{{ old('jam_operasional') }}" placeholder="mis. 09.00-17.00 WIB">
            </div>
            <div style="flex:1;min-width:170px">
                <label>Hari operasional</label>
                <input name="jadwal" value="{{ old('jadwal') }}" placeholder="mis. Senin-Jumat">
            </div>
        </div>
    </div>

    <div class="card a-warn">
        <h3 style="margin-top:0">4. Testimoni</h3>
        <label style="display:flex;align-items:flex-start;gap:8px;margin:0">
            <input type="checkbox" name="isi_testimoni" value="1" {{ old('isi_testimoni', true) ? 'checked' : '' }} style="margin-top:4px">
            <span>Ikut isi testimoni dengan AI (25 variasi)</span>
        </label>
        <p class="muted" style="margin:8px 0 0;font-size:.88rem">
            <strong>Penting:</strong> testimoni buatan AI <strong>bukan</strong> pengalaman pelanggan nyata. Menayangkannya seolah asli itu menyesatkan dan melanggar kebijakan Google.
            Gunakan hanya sebagai <em>pengisi sementara</em>, lalu ganti dengan kutipan asli sebelum publish — atau hapus centang ini dan isi sendiri nanti.
        </p>
    </div>

    <div class="card">
        <button class="btn green" style="font-size:1.05rem;padding:12px 22px" {{ $running ? 'disabled' : '' }}
            onclick="return confirm('Mulai proses otomatis? Pastikan worker antrian (queue:work) berjalan.')">
            ✨ Jalankan — Isi Semua Sampai Hijau
        </button>
        <p class="muted" style="margin:10px 0 0;font-size:.85rem">Halaman dibuat sebagai <strong>DRAFT</strong>. Publish tetap keputusan Anda setelah memeriksa sampel.</p>
    </div>
</form>

<div class="card" id="progress-card" style="display:none">
    <h3 style="margin-top:0">Progres</h3>
    <div style="height:10px;background:var(--border);border-radius:6px;overflow:hidden">
        <div id="bar" style="height:100%;width:0;background:var(--p);transition:width .4s"></div>
    </div>
    <p id="msg" class="muted" style="margin:10px 0 0"></p>
    <p id="stat" class="muted" style="margin:4px 0 0;font-size:.85rem"></p>
</div>

@if(!empty($knowledge))
<div class="card">
    <h4 style="margin:0 0 6px">Otak yang membimbing penulisan</h4>
    <p class="muted" style="margin:0;font-size:.88rem">
        {{ implode(' · ', array_map(fn($k) => $k['judul'], $knowledge)) }}
    </p>
</div>
@endif

<script>
var CITIES = @json($cities);
function filterCity(){
    var prov=document.getElementById('prov').value, sel=document.getElementById('city');
    sel.innerHTML='<option value="">— pilih kota/kabupaten —</option>';
    Object.keys(CITIES).forEach(function(k){
        if(CITIES[k].prov===prov){
            var o=document.createElement('option'); o.value=k; o.textContent=CITIES[k].nama; sel.appendChild(o);
        }
    });
}

var TOTAL_TAHAP = {{ count(\App\Services\ContentHealthService::TARGETS) + 2 }};
(function(){
    var card=document.getElementById('progress-card'), bar=document.getElementById('bar'),
        msg=document.getElementById('msg'), stat=document.getElementById('stat'),
        url="{{ route('admin.quickstart.status') }}", timer=null;
    function poll(){
        fetch(url,{headers:{'Accept':'application/json'}}).then(function(r){return r.json();}).then(function(d){
            if(d.status==='idle') return;
            card.style.display='block';
            var done=Object.keys(d.sections||{}).length + (d.faq&&d.faq.target?1:0) + (d.halaman?1:0);
            bar.style.width=Math.min(100,Math.round(done/TOTAL_TAHAP*100))+'%';
            msg.textContent=d.message||'';
            stat.textContent='Panggilan AI: '+(d.calls||0)+' · token: '+(d.tokens||0)+(d.halaman?(' · halaman: '+d.halaman):'');
            if(d.status==='done'||d.status==='error'){
                bar.style.width='100%';
                if(d.status==='error') bar.style.background='#c0392b';
                clearInterval(timer);
                setTimeout(function(){location.reload();},3000);
            }
        }).catch(function(){});
    }
    timer=setInterval(poll,3000); poll();
})();
</script>
@endsection
