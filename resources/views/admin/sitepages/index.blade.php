@extends('admin.layout')
@section('title', 'Halaman Statis')

@section('content')
<div class="card a-info">
    <h2 style="margin:0 0 4px">Halaman Statis</h2>
    <p class="muted" style="margin:0">Halaman statis dengan URL sendiri (Tentang Kami, Layanan, Kontak, dll). Yang dicentang <strong>Tampil di menu</strong> otomatis muncul di navigasi atas — menggantikan anchor yang hanya menggulung di halaman yang sama.</p>
</div>

@if($pages->isEmpty())
<div class="card a-ok">
    <h3 style="margin-top:0">Mulai Cepat — Muat Paket Halaman</h3>
    <p class="muted" style="margin-top:0">Tiap jenis usaha butuh struktur menu berbeda. Pilih paket yang paling mendekati, lalu <strong>sunting isinya</strong> dengan data bisnis yang sebenarnya.</p>
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(230px,1fr));margin-top:14px">
        @foreach($presets as $key => $pre)
            <form method="POST" action="{{ route('admin.sitepages.preset') }}">
                @csrf
                <input type="hidden" name="preset" value="{{ $key }}">
                <div style="border:1px solid var(--border);border-radius:12px;padding:14px;height:100%;display:flex;flex-direction:column">
                    <strong>{{ $pre['label'] }}</strong>
                    <p class="muted" style="margin:4px 0 8px;font-size:.83rem;flex:1">{{ $pre['desc'] }}</p>
                    <p class="muted" style="margin:0 0 10px;font-size:.78rem">
                        {{ implode(' · ', array_map(fn($x) => $x['menu_label'], $pre['pages'])) }}
                    </p>
                    <button class="btn sm">Muat {{ count($pre['pages']) }} halaman</button>
                </div>
            </form>
        @endforeach
    </div>
</div>
@endif

<div class="card">
    <h3 style="margin-top:0">Tambah Halaman</h3>
    <form method="POST" action="{{ route('admin.sitepages.store') }}">
        @csrf
        <div class="row" style="gap:12px;flex-wrap:wrap">
            <div style="flex:2;min-width:220px">
                <label>Judul <span style="color:var(--red)">*</span></label>
                <input name="title" required placeholder="mis. Tentang Kami">
            </div>
            <div style="flex:2;min-width:200px">
                <label>Slug URL <span class="muted">(kosong = otomatis)</span></label>
                <input name="slug" placeholder="tentang-kami">
            </div>
            <div style="flex:1;min-width:150px">
                <label>Teks di menu</label>
                <input name="menu_label" placeholder="Tentang">
            </div>
            <div style="min-width:90px">
                <label>Urutan</label>
                <input type="number" name="sort_order" value="0" min="0" max="999">
            </div>
        </div>
        <label style="margin-top:10px">Deskripsi singkat (meta description)</label>
        <input name="meta_description" maxlength="255" placeholder="Ringkasan 1-2 kalimat untuk hasil pencarian">
        <label style="margin-top:10px">Gambar utama / hero <span class="muted">(URL, opsional)</span></label>
        <input name="hero_image" placeholder="https://…/gambar.jpg">

        <div style="margin-top:14px;padding:14px;background:#f8fafc;border:1px solid var(--border);border-radius:10px">
            <strong style="font-size:.9rem">Gambar isi halaman (maks 4)</strong>
            <p class="muted" style="margin:4px 0 10px;font-size:.83rem">
                Sisipkan di posisi mana pun dalam tulisan dengan token
                <code>{{ '{{gambar1}}' }}</code> … <code>{{ '{{gambar4}}' }}</code>, atau
                <code>{{ '{{galeri}}' }}</code> untuk semuanya sekaligus.
                Bila token tidak dipakai, gambar tampil otomatis sebagai galeri di bawah.
            </p>
            <p class="muted" style="margin:0 0 10px;font-size:.83rem;padding:8px 10px;background:#eef2ff;border-radius:8px">
                <strong>Tidak perlu unggah ulang:</strong> pakai gambar yang sudah ada di Pengaturan dengan
                <code>{{ '{{situs_tentang}}' }}</code>, <code>{{ '{{situs_keunggulan}}' }}</code>,
                <code>{{ '{{situs_solusi}}' }}</code>, <code>{{ '{{situs_proses}}' }}</code>,
                <code>{{ '{{situs_hero}}' }}</code>, atau <code>{{ '{{situs_galeri}}' }}</code>.<br>
                Gambar hero juga <strong>terisi otomatis</strong> sesuai topik slug — mis. <code>/tentang-kami</code>
                memakai gambar “Tentang”, <code>/layanan</code> memakai gambar “Keunggulan”.
            </p>
            @for($i = 1; $i <= 4; $i++)
                <div class="row" style="gap:10px;margin-bottom:8px;flex-wrap:wrap">
                    <div style="flex:2;min-width:220px">
                        <input name="image_{{ $i }}" placeholder="URL gambar {{ $i }}">
                    </div>
                    <div style="flex:2;min-width:200px">
                        <input name="image_{{ $i }}_alt" placeholder="Alt text gambar {{ $i }} (opsional)">
                    </div>
                </div>
            @endfor
        </div>
        <label style="margin-top:10px">Isi halaman <span class="muted">(HTML; bisa pakai {{ '{{brand}}' }} dan {{ '{{year}}' }})</span></label>
        <textarea name="content" rows="8" placeholder="&lt;h2&gt;Judul&lt;/h2&gt;&lt;p&gt;Isi…&lt;/p&gt;"></textarea>
        <div class="row" style="gap:16px;margin-top:10px;flex-wrap:wrap">
            <label style="display:flex;align-items:center;gap:6px;margin:0"><input type="checkbox" name="show_in_nav" value="1" checked> Tampil di menu atas</label>
            <label style="display:flex;align-items:center;gap:6px;margin:0"><input type="checkbox" name="show_in_footer" value="1" checked> Tampil di footer</label>
            <label style="display:flex;align-items:center;gap:6px;margin:0"><input type="checkbox" name="is_active" value="1" checked> Aktif</label>
        </div>
        <button class="btn" style="margin-top:12px">Simpan Halaman</button>
    </form>
</div>

<div class="card">
    <h3 style="margin-top:0">Daftar Halaman ({{ $pages->count() }})</h3>
    @if($pages->isEmpty())
        <p class="muted" style="margin:0">Belum ada halaman. Gunakan <strong>Muat Paket Halaman</strong> di atas, atau buat sendiri lewat formulir.</p>
    @else
        @foreach($pages as $p)
        <details style="border-bottom:1px solid var(--border);padding:10px 0">
            <summary style="cursor:pointer">
                <strong>{{ $p->title }}</strong>
                <span class="muted" style="font-size:.85rem">/{{ $p->slug }}</span>
                @if(! $p->is_active)<span style="color:var(--red);font-size:.8rem"> · nonaktif</span>@endif
                @if($p->show_in_nav)<span class="muted" style="font-size:.8rem"> · di menu</span>@endif
            </summary>
            <form method="POST" action="{{ route('admin.sitepages.update', $p) }}" style="margin-top:10px">
                @csrf @method('PUT')
                <div class="row" style="gap:12px;flex-wrap:wrap">
                    <div style="flex:2;min-width:200px"><label>Judul</label><input name="title" value="{{ $p->title }}" required></div>
                    <div style="flex:2;min-width:180px"><label>Slug</label><input name="slug" value="{{ $p->slug }}"></div>
                    <div style="flex:1;min-width:140px"><label>Teks menu</label><input name="menu_label" value="{{ $p->menu_label }}"></div>
                    <div style="min-width:90px"><label>Urutan</label><input type="number" name="sort_order" value="{{ $p->sort_order }}"></div>
                </div>
                <label style="margin-top:8px">Deskripsi singkat</label>
                <input name="meta_description" value="{{ $p->meta_description }}" maxlength="255">
                <label style="margin-top:8px">Gambar utama / hero</label>
                <input name="hero_image" value="{{ $p->hero_image }}">

                <div style="margin-top:12px;padding:12px;background:#f8fafc;border:1px solid var(--border);border-radius:10px">
                    <strong style="font-size:.88rem">Gambar isi halaman</strong>
                    <p class="muted" style="margin:4px 0 8px;font-size:.8rem">Token: <code>{{ '{{gambar1}}' }}</code>–<code>{{ '{{gambar4}}' }}</code> atau <code>{{ '{{galeri}}' }}</code></p>
                    @for($i = 1; $i <= 4; $i++)
                        <div class="row" style="gap:10px;margin-bottom:8px;flex-wrap:wrap">
                            <div style="flex:2;min-width:200px">
                                <input name="image_{{ $i }}" value="{{ $p->{'image_' . $i} }}" placeholder="URL gambar {{ $i }}">
                            </div>
                            <div style="flex:2;min-width:180px">
                                <input name="image_{{ $i }}_alt" value="{{ $p->{'image_' . $i . '_alt'} }}" placeholder="Alt text {{ $i }}">
                            </div>
                        </div>
                    @endfor
                </div>
                <label style="margin-top:8px">Isi halaman</label>
                <textarea name="content" rows="10">{{ $p->content }}</textarea>
                <div class="row" style="gap:16px;margin-top:10px;flex-wrap:wrap">
                    <label style="display:flex;align-items:center;gap:6px;margin:0"><input type="checkbox" name="show_in_nav" value="1" @checked($p->show_in_nav)> Menu atas</label>
                    <label style="display:flex;align-items:center;gap:6px;margin:0"><input type="checkbox" name="show_in_footer" value="1" @checked($p->show_in_footer)> Footer</label>
                    <label style="display:flex;align-items:center;gap:6px;margin:0"><input type="checkbox" name="is_active" value="1" @checked($p->is_active)> Aktif</label>
                    <div class="right" style="display:flex;gap:8px">
                        <a class="btn sm light" href="{{ url('/' . $p->slug) }}" target="_blank" rel="noopener">Lihat ↗</a>
                        <button class="btn sm">Simpan</button>
                    </div>
                </div>
            </form>
            <form method="POST" action="{{ route('admin.sitepages.destroy', $p) }}" style="margin-top:8px"
                  onsubmit="return confirm('Hapus halaman ini?')">
                @csrf @method('DELETE')
                <button class="btn sm" style="background:var(--red)">Hapus</button>
            </form>
        </details>
        @endforeach
    @endif
</div>
@endsection
