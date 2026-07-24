@extends('admin.layout')
@section('title', 'Layanan & Harga')

@section('content')
<div class="card a-p">
    <h2 style="margin:0 0 4px">Layanan &amp; Harga</h2>
    <p class="muted" style="margin:0">
        Lengkapi detail tiap layanan agar katalog yang tampil di salespage lebih kaya dan konsisten.
    </p>
</div>

@if($services->isEmpty())
    <div class="card">
        <p class="muted" style="margin:0">Belum ada layanan. Buat lewat <a href="{{ route('admin.imports.index') }}">Import Data</a>.</p>
    </div>
@else
    @foreach($services as $sv)
    <div class="card">
        <form method="POST" action="{{ route('admin.services.update', $sv) }}">
            @csrf @method('PUT')
            <div class="row" style="gap:12px;flex-wrap:wrap;align-items:flex-end">
                <div style="flex:2;min-width:220px">
                    <label style="margin:0">Nama layanan</label>
                    <input name="name" value="{{ $sv->name }}" required>
                </div>
                <div style="flex:1;min-width:170px">
                    <label style="margin:0">Harga mulai dari</label>
                    <input name="price_from" value="{{ $sv->price_from }}" placeholder="mis. Rp100.000/sesi">
                </div>
                <div style="min-width:90px">
                    <label style="margin:0">Urutan</label>
                    <input type="number" name="sort_order" value="{{ $sv->sort_order }}" min="0" max="999">
                </div>
            </div>

            <label style="margin-top:10px">Ringkasan singkat</label>
            <input name="description" value="{{ $sv->description }}" maxlength="300" placeholder="Apa yang termasuk dalam layanan ini">

            <label style="margin-top:10px">Gambar layanan</label>
            <input name="image" value="{{ $sv->image }}" placeholder="https://.../gambar.jpg">

            <div class="row" style="margin-top:12px;align-items:center">
                <label style="display:flex;align-items:center;gap:6px;margin:0">
                    <input type="checkbox" name="is_active" value="1" @checked($sv->is_active)> Aktif
                </label>
                <span class="muted" style="font-size:.83rem">/{{ $sv->slug }} · {{ number_format($sv->pages_count) }} halaman</span>
                <div class="right"><button class="btn sm">Simpan</button></div>
            </div>
        </form>
    </div>
    @endforeach
@endif
@endsection
