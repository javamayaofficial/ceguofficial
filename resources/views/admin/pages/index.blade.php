@extends('admin.layout')
@section('title', 'Halaman & Publish Queue')

@section('content')
    <div class="card">
        <h3 style="margin-top:0">Publish Queue</h3>
        <p class="muted">Generate &amp; publish dipisah. Halaman draft tidak tampil di sitemap sampai dipublikasikan. Status: <span class="pill {{ $publishState }}" id="publish-pill">{{ $publishState }}</span></p>
        <div class="row">
            <form method="POST" action="{{ route('admin.publish.start') }}" class="row" style="gap:8px">
                @csrf
                <select name="import_batch_id" style="width:auto">
                    <option value="">Semua draft</option>
                    @foreach($batches as $b)<option value="{{ $b->id }}">{{ $b->original_filename }}</option>@endforeach
                </select>
                <button class="btn green">▶ Mulai Publish</button>
            </form>
            <form method="POST" action="{{ route('admin.publish.pause') }}" class="inline">@csrf<button class="btn gray">⏸ Pause</button></form>
            <form method="POST" action="{{ route('admin.publish.resume') }}" class="inline">@csrf<button class="btn">⏵ Resume</button></form>
        </div>
        <div id="publish-live" class="muted" style="margin-top:12px">
            @if(($publishMeta['target_count'] ?? 0) > 0)
                Target publish aktif: {{ number_format((int) ($publishMeta['target_count'] ?? 0)) }} halaman.
            @else
                Menunggu aksi publish berikutnya.
            @endif
        </div>
    </div>

    <div class="card">
        <form method="GET" class="row" style="margin-bottom:12px">
            <input name="q" value="{{ $q }}" placeholder="Cari path..." style="max-width:320px">
            <select name="status" style="width:auto">
                <option value="">Semua status</option>
                <option value="draft" {{ $status==='draft'?'selected':'' }}>Draft</option>
                <option value="published" {{ $status==='published'?'selected':'' }}>Published</option>
            </select>
            <button class="btn ghost">Filter</button>
        </form>

        <table>
            <thead><tr><th>Path</th><th>Layanan</th><th>Kota</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($pages as $page)
                <tr>
                    <td class="mono"><a href="{{ url('/'.$page->path) }}" target="_blank">/{{ $page->path }}</a></td>
                    <td>{{ $page->service?->name }}</td>
                    <td>{{ $page->city?->name }}</td>
                    <td><span class="pill {{ $page->status }}">{{ $page->status }}</span></td>
                    <td class="row" style="justify-content:flex-end">
                        @if($page->status === 'draft')
                            <form method="POST" action="{{ route('admin.pages.publish', $page) }}" class="inline">@csrf<button class="btn green sm">Publish</button></form>
                        @else
                            <form method="POST" action="{{ route('admin.pages.unpublish', $page) }}" class="inline">@csrf<button class="btn gray sm">Draft-kan</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Tidak ada halaman.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $pages->links() }}
    </div>
@endsection

@push('scripts')
<script>
(() => {
    const url = "{{ route('admin.publish.status') }}";
    const flashHost = document.querySelector('.content');
    const pill = document.getElementById('publish-pill');
    const live = document.getElementById('publish-live');
    const seenKey = 'daya.publish.lastSeenCompletedAt';
    let prevState = @json($publishState);

    function ensureFlash(message){
        if(!message || !flashHost) return;
        let box = document.getElementById('publish-success-flash');
        if(!box){
            box = document.createElement('div');
            box.id = 'publish-success-flash';
            box.className = 'flash';
            flashHost.insertBefore(box, flashHost.firstChild);
        }
        box.textContent = message;
        box.scrollIntoView({behavior:'smooth', block:'start'});
        if(window.CeguAdminToast){
            window.CeguAdminToast({
                type: 'success',
                title: 'Publish Tuntas',
                message,
                duration: 6500
            });
        }
    }

    function renderLive(d){
        if(pill){
            pill.className = 'pill ' + d.state;
            pill.textContent = d.state;
        }
        if(live){
            if(d.target_count > 0){
                live.textContent = `Progress publish: ${d.completed_count.toLocaleString()} / ${d.target_count.toLocaleString()} halaman (${d.percent}%). Sisa ${d.remaining_count.toLocaleString()} halaman.`;
            }else{
                live.textContent = d.message || 'Menunggu aksi publish berikutnya.';
            }
        }
    }

    async function poll(){
        let d;
        try{
            const res = await fetch(url, {headers:{'Accept':'application/json'}});
            d = await res.json();
        }catch(e){ return; }

        renderLive(d);

        const completedAt = d.completed_at || '';
        const lastSeen = localStorage.getItem(seenKey) || '';
        if(d.state === 'idle' && completedAt && completedAt !== lastSeen && (prevState === 'running' || prevState === 'paused' || d.completed_count > 0)){
            ensureFlash(d.message || 'Publish selesai.');
            localStorage.setItem(seenKey, completedAt);
        }

        prevState = d.state;
    }

    poll();
    setInterval(poll, 2000);
})();
</script>
@endpush
