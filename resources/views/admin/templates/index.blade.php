@extends('admin.layout')
@section('title', 'Template Salespage')

@section('content')
    <div class="card">
        <div class="row">
            <p style="margin:0" class="muted">Satu template aktif dipakai oleh <strong>seluruh halaman</strong>. Mengubahnya otomatis berlaku ke semua halaman.</p>
            <a class="btn right" href="{{ route('admin.templates.create') }}">+ Template Baru</a>
        </div>
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
