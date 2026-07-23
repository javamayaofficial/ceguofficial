@extends('admin.layout')
@section('title', 'Import CSV & Generate')

@push('head')
<style>
    .cegu-bar{height:16px;background:#e2e8f0;border-radius:8px;overflow:hidden}
    .cegu-bar .fill{height:100%;background:linear-gradient(90deg,#16a34a,#22c55e);width:0;transition:width .5s ease;border-radius:8px}
    .cegu-log{background:#0f172a;color:#cbd5e1;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.78rem;padding:12px 14px;border-radius:8px;height:190px;overflow-y:auto;margin-top:14px;line-height:1.55;white-space:pre-wrap}
    .cegu-log .err{color:#fca5a5}.cegu-log .ok{color:#86efac}.cegu-log .info{color:#93c5fd}.cegu-log .mut{color:#64748b}
    .minibar{height:6px;background:#e2e8f0;border-radius:4px;overflow:hidden;margin-top:5px;min-width:120px}
    .minibar .fill{height:100%;background:#16a34a;width:0;transition:width .5s}
    .live-card{border:1px solid var(--border);border-left:4px solid var(--p);border-radius:12px;padding:18px 20px;margin-bottom:14px;background:#fff}
    .blink{animation:blink 1.1s infinite}@keyframes blink{50%{opacity:.35}}
</style>
@endpush

@section('content')
    <div class="card">
        <h3 style="margin-top:0">Upload CSV</h3>
        <p class="muted">Kolom wajib: <code>layanan,kota,kecamatan,kelurahan</code>. <strong>Kolom opsional apa pun otomatis menjadi data lokal per halaman</strong> — mis. <code>harga</code>, <code>jumlah_tutor</code>, <code>landmark</code>, <code>sekolah</code> — dan bisa dipakai sebagai token <code>@{{harga}}</code> dst., dirangkai otomatis di token <code>@{{fakta_lokal}}</code>, serta dianyam ke AI Summary. Meng-upload ulang lokasi yang sama dengan kolom baru akan <strong>memperkaya halaman yang sudah ada</strong> (tanpa duplikat). <strong>Mode cross-join:</strong> isi "Daftar Layanan" dan upload CSV <em>tanpa</em> kolom layanan (cukup <code>kota,kecamatan,kelurahan</code>) — setiap lokasi otomatis digandakan untuk semua layanan tersebut (mis. 500 lokasi × 8 layanan = 4.000 halaman dari satu file). Setelah upload, generate berjalan otomatis di background (queue). Pastikan worker aktif: <code>php artisan queue:work</code>.</p>
        <form method="POST" action="{{ route('admin.imports.store') }}" enctype="multipart/form-data" class="row" style="align-items:flex-end">
            @csrf
            <div style="flex:1"><label>File CSV (maks 50 MB)</label><input type="file" name="csv" accept=".csv,.txt" required></div>
            <div style="flex:1"><label>Daftar Layanan/Keyword <span class="muted">(opsional — mode cross-join)</span></label>
                <textarea name="layanan_list" rows="2" placeholder="Satu per baris atau pisah koma, mis.:&#10;Les Privat Matematika&#10;Guru Ngaji ke Rumah">{{ old('layanan_list') }}</textarea>
            </div>
            <button class="btn green">⬆ Upload &amp; Generate</button>
        </form>
    </div>

    {{-- ===== LIVE: batch yang sedang berjalan ===== --}}
    @php $active = $batches->getCollection()->filter(fn($b)=>in_array($b->status,['queued','processing','paused'])); @endphp
    <div id="live-zone">
        @if($active->isNotEmpty())
            <h3 style="margin:18px 0 10px">Generate Berjalan</h3>
        @endif
        @foreach($active as $b)
            <div class="live-card" data-live="{{ $b->id }}" data-status="{{ $b->status }}">
                <div class="row" style="margin-bottom:10px">
                    <strong class="mono">{{ $b->original_filename }}</strong>
                    <span class="pill {{ $b->status }} js-pill">{{ $b->status }}</span>
                    <span class="right row">
                        <span class="js-pause">
                            <form method="POST" action="{{ route('admin.imports.pause', $b) }}" class="inline">@csrf<button class="btn gray sm">⏸ Pause</button></form>
                        </span>
                        <span class="js-resume" style="display:none">
                            <form method="POST" action="{{ route('admin.imports.resume', $b) }}" class="inline">@csrf<button class="btn green sm">⏵ Resume</button></form>
                        </span>
                    </span>
                </div>
                <div class="cegu-bar"><div class="fill js-fill"></div></div>
                <div class="row" style="margin-top:10px;gap:22px;font-size:.85rem">
                    <span><strong class="js-pct">0%</strong></span>
                    <span class="muted">Diproses: <strong class="js-proc">{{ $b->processed_rows }}</strong> / {{ $b->total_rows }}</span>
                    <span class="muted">Generated: <strong class="js-gen" style="color:#16a34a">{{ $b->generated_count }}</strong></span>
                    <span class="muted">Gagal: <strong class="js-fail" style="color:#dc2626">{{ $b->failed_count }}</strong></span>
                </div>
                <div class="cegu-log" id="log-{{ $b->id }}"><span class="mut">Menunggu progres…</span></div>
            </div>
        @endforeach
    </div>

    {{-- ===== Riwayat ===== --}}
    <div class="card">
        <h3 style="margin-top:0">Riwayat Import</h3>
        <table>
            <thead><tr><th>File</th><th>Status</th><th>Progress</th><th>Generated</th><th>Gagal</th><th></th></tr></thead>
            <tbody>
            @forelse($batches as $b)
                <tr data-batch="{{ $b->id }}" data-status="{{ $b->status }}">
                    <td class="mono">{{ $b->original_filename }}<br><span class="muted">{{ $b->created_at?->diffForHumans() }}</span></td>
                    <td class="js-status"><span class="pill {{ $b->status }}">{{ $b->status }}</span></td>
                    <td style="min-width:150px">
                        <span class="js-progress">{{ number_format($b->processed_rows) }} / {{ number_format($b->total_rows) }}</span>
                        <div class="minibar"><div class="fill js-mini" style="width:{{ $b->total_rows ? round($b->processed_rows/$b->total_rows*100) : 0 }}%"></div></div>
                    </td>
                    <td class="js-gen">{{ number_format($b->generated_count) }}</td>
                    <td class="js-fail">{{ number_format($b->failed_count) }}</td>
                    <td class="row" style="justify-content:flex-end">
                        @if(in_array($b->status, ['processing','queued']))
                            <form method="POST" action="{{ route('admin.imports.pause', $b) }}" class="inline">@csrf<button class="btn gray sm">Pause</button></form>
                        @elseif($b->status === 'paused')
                            <form method="POST" action="{{ route('admin.imports.resume', $b) }}" class="inline">@csrf<button class="btn green sm">Resume</button></form>
                        @endif
                        <form method="POST" action="{{ route('admin.imports.destroy', $b) }}" class="inline" onsubmit="return confirm('Hapus catatan import?')">@csrf @method('DELETE')<button class="btn red sm">Hapus</button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Belum ada import.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $batches->links() }}
    </div>
@endsection

@push('scripts')
<script>
const token = document.querySelector('meta[name=csrf-token]').content;
const base = "{{ url('admin/imports') }}";
const state = {};   // batchId -> {processed, status, errCount}

function ts(){ return new Date().toLocaleTimeString('id-ID'); }

function logLine(box, cls, text){
    if(!box) return;
    if(box.querySelector('.mut')) box.innerHTML = '';
    const line = document.createElement('div');
    if(cls) line.className = cls;
    line.textContent = `[${ts()}] ${text}`;
    box.appendChild(line);
    box.scrollTop = box.scrollHeight;
}

async function refresh(id){
    let d;
    try{
        const res = await fetch(`${base}/${id}/status`, {headers:{'X-CSRF-TOKEN':token}});
        d = await res.json();
    }catch(e){ return; }

    const prev = state[id] || {processed:-1, status:null, errCount:0};
    const box = document.getElementById('log-'+id);

    // --- update kartu live (jika ada) ---
    const live = document.querySelector(`[data-live="${id}"]`);
    if(live){
        live.querySelector('.js-fill').style.width = d.percent + '%';
        live.querySelector('.js-pct').textContent = d.percent + '%';
        live.querySelector('.js-proc').textContent = d.processed_rows.toLocaleString();
        live.querySelector('.js-gen').textContent = d.generated_count.toLocaleString();
        live.querySelector('.js-fail').textContent = d.failed_count.toLocaleString();
        const pill = live.querySelector('.js-pill');
        pill.className = 'pill ' + d.status + ' js-pill' + (d.status==='processing'?' blink':'');
        pill.textContent = d.status;
        live.querySelector('.js-pause').style.display = (d.status==='processing'||d.status==='queued')?'':'none';
        live.querySelector('.js-resume').style.display = (d.status==='paused')?'':'none';
    }

    // --- update baris riwayat ---
    const tr = document.querySelector(`tr[data-batch="${id}"]`);
    if(tr){
        tr.querySelector('.js-status').innerHTML = `<span class="pill ${d.status}">${d.status}</span>`;
        tr.querySelector('.js-progress').textContent = `${d.processed_rows.toLocaleString()} / ${d.total_rows.toLocaleString()}`;
        tr.querySelector('.js-mini').style.width = d.percent + '%';
        tr.querySelector('.js-gen').textContent = d.generated_count.toLocaleString();
        tr.querySelector('.js-fail').textContent = d.failed_count.toLocaleString();
    }

    // --- log: status berubah ---
    if(prev.status !== d.status){
        logLine(box, 'info', `Status: ${prev.status ?? '—'} → ${d.status}`);
    }
    // --- log: ada progres baru ---
    if(d.processed_rows !== prev.processed){
        logLine(box, '', `Progres ${d.processed_rows.toLocaleString()}/${d.total_rows.toLocaleString()} (${d.percent}%) — generated ${d.generated_count.toLocaleString()}, gagal ${d.failed_count.toLocaleString()}`);
    }
    // --- log: error baru ---
    if(d.errors && d.errors.length > prev.errCount){
        d.errors.slice(prev.errCount).forEach(e => logLine(box, 'err', '⚠ ' + e));
    }
    // --- selesai ---
    if(d.status === 'completed' && prev.status !== 'completed'){
        logLine(box, 'ok', `✓ Selesai — ${d.generated_count.toLocaleString()} halaman dibuat, ${d.failed_count.toLocaleString()} gagal.`);
        setTimeout(()=>location.reload(), 1500);
    }
    if(d.status === 'failed' && prev.status !== 'failed'){
        logLine(box, 'err', '✗ Batch gagal. Cek log di atas.');
    }

    state[id] = {processed:d.processed_rows, status:d.status, errCount:(d.errors||[]).length};
}

function tick(){
    const ids = new Set();
    document.querySelectorAll('[data-live],tr[data-batch]').forEach(el=>{
        const id = el.dataset.live || el.dataset.batch;
        const st = el.dataset.status;
        if(['queued','processing','paused'].includes(st)) ids.add(id);
    });
    ids.forEach(refresh);
}

// poll tiap 2 detik; juga sekali di awal
tick();
setInterval(tick, 2000);
</script>
@endpush
