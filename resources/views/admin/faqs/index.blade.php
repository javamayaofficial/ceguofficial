@extends('admin.layout')
@section('title', 'FAQ Dinamis')

@section('content')
    <div class="card">
        <p class="muted" style="margin:0 0 8px"><strong>Import massal FAQ dari CSV</strong> — kolom: <code>question,answer,layanan</code> (layanan opsional: kosong = FAQ global; isi nama layanan = FAQ khusus layanan itu). Duplikat dilewati.</p>
        <form method="POST" action="{{ route('admin.faqs.import') }}" enctype="multipart/form-data" class="row" style="align-items:flex-end;gap:10px">
            @csrf
            <div><input type="file" name="csv" accept=".csv,.txt" required></div>
            <button class="btn">⬆ Import FAQ CSV</button>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top:0">Tambah FAQ</h3>
        <form method="POST" action="{{ route('admin.faqs.store') }}">
            @csrf
            <div class="row">
                <div style="flex:1"><label>Pertanyaan</label><input name="question" placeholder="Berapa biaya @{{layanan}}?" required></div>
                <div style="width:220px"><label>Layanan</label>
                    <select name="service_id"><option value="">— Global (semua layanan) —</option>
                        @foreach($services as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                    </select>
                </div>
            </div>
            <label>Jawaban</label>
            <textarea name="answer" rows="2" placeholder="Biaya disesuaikan dengan jenjang pendidikan..." required></textarea>
            <div class="row" style="margin-top:8px">
                <div><label style="margin:0">Urutan</label><input type="number" name="sort_order" value="0" style="width:90px"></div>
                <label class="row" style="align-self:flex-end;gap:5px;margin:0"><input type="checkbox" name="is_active" value="1" checked style="width:auto">aktif</label>
                <button class="btn green right">+ Tambah FAQ</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top:0">Daftar FAQ</h3>
        @forelse($faqs as $faq)
            <form method="POST" action="{{ route('admin.faqs.update', $faq) }}" style="border-bottom:1px solid var(--border);padding:12px 0">
                @csrf @method('PUT')
                <div class="row">
                    <div style="flex:1"><input name="question" value="{{ $faq->question }}"></div>
                    <div style="width:200px"><span class="pill {{ $faq->service_id ? 'processing':'completed' }}">{{ $faq->service?->name ?? 'Global' }}</span></div>
                </div>
                <textarea name="answer" rows="2" style="margin-top:6px">{{ $faq->answer }}</textarea>
                <div class="row" style="margin-top:6px">
                    <div><input type="number" name="sort_order" value="{{ $faq->sort_order }}" style="width:80px"></div>
                    <label class="row" style="gap:5px;margin:0"><input type="checkbox" name="is_active" value="1" style="width:auto" {{ $faq->is_active?'checked':'' }}>aktif</label>
                    <button class="btn sm">Simpan</button>
                    <button form="delf-{{ $faq->id }}" class="btn red sm right" onclick="return confirm('Hapus FAQ?')">Hapus</button>
                </div>
            </form>
            <form id="delf-{{ $faq->id }}" method="POST" action="{{ route('admin.faqs.destroy', $faq) }}">@csrf @method('DELETE')</form>
        @empty
            <p class="muted">Belum ada FAQ.</p>
        @endforelse
    </div>
@endsection
