@extends('admin.layout')
@section('title', 'Template Salespage')

@section('content')
    <div class="card">
        <div class="row">
            <p style="margin:0" class="muted">Satu template aktif dipakai oleh <strong>seluruh halaman</strong>. Mengubahnya otomatis berlaku ke semua halaman.</p>
            <a class="btn right" href="{{ route('admin.templates.create') }}">+ Template Baru</a>
        </div>
    </div>

    <div class="card a-ok">
        <div class="row">
            <div style="flex:1;min-width:260px">
                <h3 style="margin:0 0 4px">Template Kerangka Siap Pakai</h3>
                <p class="muted" style="margin:0;font-size:.88rem">
                    12 kerangka copywriting (AIDA, PAS, BAB, 4P, QUEST, TOFU, MOFU, BOFU, VSL,
                    Advertorial, Long Form, FSP). Semuanya memakai token standar sehingga
                    variasi konten dan kata kunci tetap berfungsi.
                </p>
            </div>
            <form method="POST" action="{{ route('admin.templates.import') }}">
                @csrf
                <button class="btn green" onclick="return confirm('Muat template kerangka? Yang sudah ada akan dilewati, template aktif tidak terganggu.')">
                    Muat 12 Template Kerangka
                </button>
            </form>
        </div>
        <p class="muted" style="margin:10px 0 0;font-size:.83rem">
            Semua dimuat dalam keadaan <strong>nonaktif</strong> sehingga template yang sedang dipakai tidak terganggu.
            Aman diulang: yang sudah ada tidak akan diduplikasi.
        </p>
    </div>

    <div class="card">
        <table>
            <thead><tr><th>Nama</th><th>Status</th><th>Diperbarui</th><th></th></tr></thead>
            <tbody>
            @foreach($templates as $t)
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
    </div>
@endsection
