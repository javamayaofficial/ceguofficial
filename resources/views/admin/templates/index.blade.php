@extends('admin.layout')
@section('title', 'Template Salespage')

@section('content')
    <div class="card">
        <div class="row">
            <p style="margin:0" class="muted">Template kini dipisah antara <strong>salespage</strong> dan <strong>beranda</strong>. Satu template aktif berlaku untuk tiap jenisnya masing-masing.</p>
            <a class="btn right" href="{{ route('admin.templates.create') }}">+ Template Baru</a>
        </div>
    </div>

    <div class="card a-ok">
        <div class="row">
            <div style="flex:1;min-width:260px">
                <h3 style="margin:0 0 4px">Template Kerangka Siap Pakai</h3>
                <p class="muted" style="margin:0;font-size:.88rem">
                    Tersedia kerangka salespage dan beranda. Aman diulang: yang sudah ada akan dilewati, dan semuanya dimuat dalam kondisi nonaktif.
                </p>
            </div>
            <form method="POST" action="{{ route('admin.templates.import') }}">
                @csrf
                <button class="btn green" onclick="return confirm('Muat template kerangka? Yang sudah ada akan dilewati, template aktif tidak terganggu.')">
                    Muat Template Kerangka
                </button>
            </form>
        </div>
    </div>

    @foreach([
        ['judul' => 'Template Salespage', 'ket' => 'Dipakai untuk halaman wilayah / layanan.', 'items' => $salespage],
        ['judul' => 'Template Beranda', 'ket' => 'Dipakai untuk halaman utama saat mode template beranda aktif.', 'items' => $home],
    ] as $grup)
        <div class="card">
            <div class="row" style="margin-bottom:10px">
                <div style="flex:1;min-width:260px">
                    <h3 style="margin:0 0 4px">{{ $grup['judul'] }}</h3>
                    <p class="muted" style="margin:0;font-size:.87rem">{{ $grup['ket'] }}</p>
                </div>
            </div>

            @if($grup['items']->isEmpty())
                <p class="muted" style="margin:0">Belum ada template pada grup ini.</p>
            @else
                <table>
                    <thead><tr><th>Nama</th><th>Status</th><th>Diperbarui</th><th></th></tr></thead>
                    <tbody>
                    @foreach($grup['items'] as $t)
                        <tr>
                            <td><strong>{{ $t->name }}</strong></td>
                            <td>@if($t->is_active)<span class="pill published">AKTIF</span>@else<span class="pill draft">nonaktif</span>@endif</td>
                            <td class="muted">{{ $t->updated_at?->diffForHumans() }}</td>
                            <td class="row" style="justify-content:flex-end">
                                <a class="btn ghost sm" href="{{ route('admin.templates.edit', $t) }}">Edit</a>
                                @unless($t->is_active)
                                    <form method="POST" action="{{ route('admin.templates.activate', $t) }}" class="inline">@csrf
                                        <button class="btn green sm">Aktifkan</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.templates.destroy', $t) }}" class="inline" onsubmit="return confirm('Hapus template ini?')">
                                        @csrf @method('DELETE')<button class="btn red sm">Hapus</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach
@endsection
