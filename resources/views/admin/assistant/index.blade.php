@extends('admin.layout')
@section('title', 'Asisten SEO')

@section('content')
@php
    $skor = $audit['skor'] ?? 0;
    $warna = $skor >= 80 ? '#1a9e55' : ($skor >= 50 ? '#e0a800' : '#c0392b');
    $masalah = $audit['masalah'] ?? [];
@endphp

<div class="card a-p">
    <div class="row">
        <div>
            <h2 style="margin:0">Asisten SEO</h2>
            <p class="muted" style="margin:4px 0 0">Konsultan SEO yang membaca kondisi nyata situs Anda. Ajak diskusi, minta laporan, atau minta dijalankan.</p>
        </div>
        <div class="right" style="text-align:center">
            <div style="font-size:2rem;font-weight:800;color:{{ $warna }}">{{ $skor }}</div>
            <div class="muted" style="font-size:.75rem">skor kesehatan</div>
        </div>
    </div>

    @if(! $configured)
        <p style="color:var(--red);margin:10px 0 0">Kunci API AI belum diatur. Isi <code>AI_DRIVER</code>, <code>AI_API_KEY</code>, <code>AI_MODEL</code> di <code>.env</code>, lalu jalankan <code>php artisan config:clear</code>.</p>
    @endif
</div>

@if(count($masalah))
<div class="card">
    <h3 style="margin-top:0">Masalah terdeteksi ({{ count($masalah) }})</h3>
    @foreach($masalah as $m)
        @php $pw = $m['prioritas']==='kritis' ? '#c0392b' : ($m['prioritas']==='tinggi' ? '#e0700a' : '#888'); @endphp
        <div style="border-left:3px solid {{ $pw }};padding:6px 0 6px 10px;margin-bottom:10px">
            <strong>{{ ucfirst($m['prioritas']) }} — {{ $m['masalah'] }}</strong>
            <div class="muted" style="font-size:.88rem">Dampak: {{ $m['dampak'] }}</div>
            <div style="font-size:.88rem">→ {{ $m['aksi'] }}</div>
        </div>
    @endforeach
</div>
@endif

@php $tugas = $audit['tugas_owner'] ?? []; @endphp

@if(!empty($knowledge))
<div class="card a-ok">
    <h3 style="margin-top:0">ð§© Otak Asisten ({{ count($knowledge) }} file pengetahuan)</h3>
    <p class="muted" style="margin:0 0 10px">File pengetahuan (Markdown) di <code>storage/app/brain/</code>. Hanya file yang <strong>relevan</strong> dengan pertanyaan yang dikirim ke AI â sisanya tidak, agar hemat token. Tambah/edit file di server untuk memperkaya asisten.</p>
    <table style="width:100%;border-collapse:collapse;font-size:.88rem">
        <thead><tr style="text-align:left;border-bottom:1px solid var(--border)">
            <th style="padding:6px 0">File</th><th>Pemicu</th><th style="width:80px;text-align:right">Ukuran</th>
        </tr></thead>
        <tbody>
        @foreach($knowledge as $kb)
            <tr style="border-bottom:1px solid var(--border)">
                <td style="padding:6px 0"><strong>{{ $kb['judul'] }}</strong><br><span class="muted" style="font-size:.8rem">{{ $kb['file'] }}</span></td>
                <td class="muted">{{ $kb['selalu'] ? 'selalu aktif' : implode(', ', array_slice($kb['pemicu'],0,6)) }}</td>
                <td style="text-align:right">{{ number_format($kb['chars']) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

@if(count($tugas))
<div class="card a-info">
    <h3 style="margin-top:0">Yang perlu Anda kerjakan sendiri</h3>
    <p class="muted" style="margin:0 0 10px">Asisten mengerjakan yang bisa diotomatiskan. Hal di bawah ini di luar jangkauannya — butuh Anda.</p>
    @foreach($tugas as $t)
        @php $pw = $t['prioritas']==='kritis' ? '#c0392b' : ($t['prioritas']==='tinggi' ? '#e0700a' : ($t['prioritas']==='info' ? '#0a7ea4' : '#888')); @endphp
        <div style="border-left:3px solid {{ $pw }};padding:6px 0 6px 10px;margin-bottom:10px">
            <strong>{{ $t['tugas'] }}</strong>
            <div class="muted" style="font-size:.88rem">Kenapa AI tidak bisa: {{ $t['kenapa'] }}</div>
            <div style="font-size:.88rem">→ {{ $t['cara'] }}</div>
        </div>
    @endforeach
</div>
@endif

<div class="card">
    <div class="row" style="margin-bottom:8px">
        <h3 style="margin:0">Diskusi</h3>
        <div class="right"><button class="btn sm" onclick="mintaLaporan()">📋 Buat Laporan Kesehatan</button></div>
    </div>

    <div id="chat" style="max-height:460px;overflow-y:auto;padding:4px 2px"></div>

    <div id="saran" style="display:flex;gap:6px;flex-wrap:wrap;margin:10px 0">
        <button class="btn sm light" onclick="tanyaCepat(this)">Apa yang harus saya perbaiki duluan?</button>
        <button class="btn sm light" onclick="tanyaCepat(this)">Strategi 30 hari ke depan?</button>
        <button class="btn sm light" onclick="tanyaCepat(this)">Kenapa halaman saya belum dapat lead?</button>
        <button class="btn sm light" onclick="tanyaCepat(this)">Apakah aman kalau saya publish 1 juta halaman?</button>
    </div>

    <div style="display:flex;gap:8px;align-items:flex-end">
        <textarea id="q" rows="2" placeholder="Tanya apa saja soal SEO situs ini…" style="flex:1;resize:vertical"></textarea>
        <button class="btn" id="kirim" onclick="kirim()">Kirim</button>
    </div>
</div>

<script>
var riwayat = [];
var URL_ASK = "{{ route('admin.assistant.ask') }}";
var URL_REPORT = "{{ route('admin.assistant.report') }}";
var URL_EXEC = "{{ route('admin.assistant.execute') }}";
var CSRF = "{{ csrf_token() }}";

function esc(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }

function bubble(who, text){
    var wrap=document.createElement('div');
    wrap.style.cssText='margin:10px 0;display:flex;'+(who==='user'?'justify-content:flex-end':'');
    var b=document.createElement('div');
    b.style.cssText='max-width:82%;padding:10px 12px;border-radius:10px;white-space:pre-wrap;line-height:1.5;font-size:.93rem;'
        + (who==='user' ? 'background:var(--p);color:#fff' : 'background:var(--card-alt,#f4f4f7);border:1px solid var(--border)');
    b.innerHTML = esc(text);
    wrap.appendChild(b);
    document.getElementById('chat').appendChild(wrap);
    document.getElementById('chat').scrollTop = 99999;
    return b;
}

function tombolAksi(list){
    if(!list || !list.length) return;
    var box=document.createElement('div');
    box.style.cssText='margin:4px 0 14px;display:flex;gap:8px;flex-wrap:wrap';
    list.forEach(function(a){
        var btn=document.createElement('button');
        btn.className='btn sm'+(a.danger?'':' light');
        btn.textContent='▶ '+a.label;
        btn.title=a.alasan||'';
        btn.onclick=function(){
            if(a.danger && !confirm('Aksi ini berdampak besar. Lanjutkan?')) return;
            btn.disabled=true; btn.textContent='⏳ '+a.label;
            fetch(URL_EXEC,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
                body:JSON.stringify({id:a.id})})
              .then(function(r){return r.json();})
              .then(function(d){
                  if(d.redirect){ location.href=d.redirect; return; }
                  bubble('bot', (d.ok?'✅ ':'⚠️ ')+(d.pesan||''));
                  btn.textContent='✔ '+a.label;
              })
              .catch(function(){ bubble('bot','⚠️ Gagal menjalankan aksi.'); btn.disabled=false; });
        };
        box.appendChild(btn);
    });
    document.getElementById('chat').appendChild(box);
    document.getElementById('chat').scrollTop = 99999;
}

function tanyaCepat(el){ document.getElementById('q').value = el.textContent; kirim(); }

function kirim(){
    var q=document.getElementById('q').value.trim();
    if(!q) return;
    document.getElementById('q').value='';
    document.getElementById('saran').style.display='none';
    bubble('user', q);
    riwayat.push({role:'user',content:q});
    var t=bubble('bot','⏳ Sedang menganalisis data situs…');
    document.getElementById('kirim').disabled=true;

    fetch(URL_ASK,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
        body:JSON.stringify({pertanyaan:q, riwayat:riwayat.slice(-6)})})
      .then(function(r){return r.json();})
      .then(function(d){
          t.innerHTML=esc(d.jawaban||'(kosong)');
          riwayat.push({role:'assistant',content:d.jawaban||''});
          tombolAksi(d.aksi);
      })
      .catch(function(){ t.innerHTML='⚠️ Gagal menghubungi asisten.'; })
      .finally(function(){ document.getElementById('kirim').disabled=false; });
}

function mintaLaporan(){
    document.getElementById('saran').style.display='none';
    bubble('user','Buatkan laporan kesehatan website.');
    var t=bubble('bot','⏳ Menyusun laporan…');
    fetch(URL_REPORT,{headers:{'Accept':'application/json'}})
      .then(function(r){return r.json();})
      .then(function(d){
          t.innerHTML=esc(d.jawaban||'(kosong)');
          riwayat.push({role:'assistant',content:d.jawaban||''});
          tombolAksi(d.aksi);
      })
      .catch(function(){ t.innerHTML='⚠️ Gagal membuat laporan.'; });
}

document.getElementById('q').addEventListener('keydown', function(e){
    if(e.key==='Enter' && (e.ctrlKey||e.metaKey)) kirim();
});
</script>
@endsection
