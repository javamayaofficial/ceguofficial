@extends('admin.layout')
@section('title', 'Lead WhatsApp')

@section('content')
@if(! $tersedia)
    <div class="card">
        <h2 style="margin-top:0">Lead WhatsApp</h2>
        <p class="muted" style="margin:0">Tabel pelacakan belum dibuat. Jalankan <code>php artisan migrate</code> di server.</p>
    </div>
@else
@php
    $bulanNama = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];
    $maxHari = max(1, max($hari ?: [1]));
    $maxBulan = max(1, max($perBulan ?: [1]));
    $selisihHari = $hariIni - $kemarin;
    $selisihBulan = $bulanIni - $bulanLalu;
@endphp

<div class="card a-ok">
    <h2 style="margin:0 0 4px">Lead WhatsApp</h2>
    <p class="muted" style="margin:0;font-size:.9rem">
        <strong>Yang dihitung: klik tombol WhatsApp</strong>, bukan chat yang benar-benar terkirim.
        Setelah tombol diklik, percakapan terjadi di aplikasi WhatsApp yang tidak bisa diakses server —
        sebagian orang batal sebelum mengirim pesan. Jadi jumlah chat sesungguhnya biasanya
        <strong>lebih kecil</strong> dari angka di bawah.
    </p>
</div>

{{-- ============ ANGKA UTAMA ============ --}}
<div class="card">
    <div style="display:flex;gap:30px;flex-wrap:wrap">
        <div>
            <div style="font-size:2.1rem;font-weight:800;color:#25D366">{{ number_format($hariIni) }}</div>
            <div class="muted" style="font-size:.82rem">hari ini
                @if($kemarin > 0)
                    <span style="color:{{ $selisihHari >= 0 ? '#1a9e55' : '#c0392b' }}">
                        ({{ $selisihHari >= 0 ? '+' : '' }}{{ $selisihHari }} vs kemarin)
                    </span>
                @endif
            </div>
        </div>
        <div>
            <div style="font-size:2.1rem;font-weight:800">{{ number_format($mingguIni) }}</div>
            <div class="muted" style="font-size:.82rem">minggu ini</div>
        </div>
        <div>
            <div style="font-size:2.1rem;font-weight:800">{{ number_format($bulanIni) }}</div>
            <div class="muted" style="font-size:.82rem">bulan ini
                @if($bulanLalu > 0)
                    <span style="color:{{ $selisihBulan >= 0 ? '#1a9e55' : '#c0392b' }}">
                        ({{ $selisihBulan >= 0 ? '+' : '' }}{{ $selisihBulan }} vs bulan lalu)
                    </span>
                @endif
            </div>
        </div>
        <div>
            <div style="font-size:2.1rem;font-weight:800">{{ number_format($tahunIni) }}</div>
            <div class="muted" style="font-size:.82rem">tahun ini</div>
        </div>
        <div>
            <div style="font-size:2.1rem;font-weight:800;color:#888">{{ number_format($total) }}</div>
            <div class="muted" style="font-size:.82rem">total sejak awal</div>
        </div>
    </div>
    @if($pertamaKali)
        <p class="muted" style="margin:12px 0 0;font-size:.82rem">Pencatatan dimulai {{ \Illuminate\Support\Carbon::parse($pertamaKali)->translatedFormat('d F Y') }}.</p>
    @endif
</div>

@if($total === 0)
<div class="card">
    <p class="muted" style="margin:0">
        Belum ada klik tercatat. Pastikan: (1) halaman sudah <strong>dipublish</strong> dan dikunjungi,
        (2) <strong>nomor WhatsApp sudah diisi</strong> di Pengaturan — bila masih nomor contoh, tombolnya
        mengarah ke nomor kosong dan tidak akan menghasilkan chat.
    </p>
</div>
@else

{{-- ============ TREN 30 HARI ============ --}}
<div class="card">
    <h3 style="margin-top:0">30 hari terakhir</h3>
    <div style="display:flex;align-items:flex-end;gap:3px;height:120px;margin-top:10px">
        @foreach($hari as $tgl => $jml)
            <div title="{{ $tgl }}: {{ $jml }} klik"
                 style="flex:1;background:{{ $jml > 0 ? '#25D366' : 'var(--border)' }};
                        height:{{ max(3, (int) round($jml / $maxHari * 100)) }}%;border-radius:2px 2px 0 0"></div>
        @endforeach
    </div>
    <div class="muted" style="display:flex;justify-content:space-between;font-size:.75rem;margin-top:6px">
        <span>{{ \Illuminate\Support\Carbon::parse(array_key_first($hari))->format('d M') }}</span>
        <span>puncak: {{ number_format($maxHari) }}/hari</span>
        <span>{{ \Illuminate\Support\Carbon::parse(array_key_last($hari))->format('d M') }}</span>
    </div>
</div>

{{-- ============ PER BULAN ============ --}}
<div class="card">
    <h3 style="margin-top:0">Per bulan ({{ now()->year }})</h3>
    <table style="width:100%;border-collapse:collapse;font-size:.88rem">
        <tbody>
        @for($b = 1; $b <= 12; $b++)
            @php $j = (int) ($perBulan[$b] ?? 0); @endphp
            <tr style="border-bottom:1px solid var(--border)">
                <td style="padding:5px 0;width:52px">{{ $bulanNama[$b] }}</td>
                <td>
                    <div style="background:var(--border);border-radius:4px;height:14px;overflow:hidden">
                        <div style="width:{{ $j > 0 ? max(2, (int) round($j / $maxBulan * 100)) : 0 }}%;background:#25D366;height:100%"></div>
                    </div>
                </td>
                <td style="width:70px;text-align:right;font-weight:600">{{ number_format($j) }}</td>
            </tr>
        @endfor
        </tbody>
    </table>
</div>

{{-- ============ SEBARAN NOMOR CS (ROTATOR) ============ --}}
@if(!empty($perNomor))
<div class="card">
    <div class="row">
        <h3 style="margin:0">Sebaran ke nomor CS <span class="muted" style="font-weight:400;font-size:.85rem">(rotator)</span></h3>
        @if($totalTerbuka > 0)
            <div class="right muted" style="font-size:.85rem">
                Terkonfirmasi membuka WhatsApp: <strong>{{ number_format($totalTerbuka) }}</strong>
                ({{ $total > 0 ? round($totalTerbuka / $total * 100) : 0 }}%)
            </div>
        @endif
    </div>
    <p class="muted" style="margin:8px 0 10px;font-size:.85rem">
        Kolom <strong>Terbuka</strong> = klik yang diikuti perpindahan ke aplikasi WhatsApp.
        Ini <em>proksi</em>, bukan kepastian — sebagian perangkat tidak mengirim sinyalnya, jadi angka aslinya bisa lebih tinggi.
    </p>
    <table style="width:100%;border-collapse:collapse;font-size:.88rem">
        <thead><tr style="text-align:left;border-bottom:1px solid var(--border)">
            <th style="padding:6px 0">Nomor CS</th>
            <th style="width:90px;text-align:right">Klik</th>
            <th style="width:110px;text-align:right">Terbuka</th>
            <th style="width:80px;text-align:right">Rasio</th>
        </tr></thead>
        <tbody>
        @foreach($perNomor as $n)
            @php
                $jml = (int) $n['jml']; $tb = (int) ($n['terbuka'] ?? 0);
                $rasio = $jml > 0 ? round($tb / $jml * 100) : 0;
            @endphp
            <tr style="border-bottom:1px solid var(--border)">
                <td style="padding:6px 0;font-family:monospace">+{{ $n['wa_number'] }}</td>
                <td style="text-align:right;font-weight:600">{{ number_format($jml) }}</td>
                <td style="text-align:right">{{ number_format($tb) }}</td>
                <td style="text-align:right">{{ $rasio }}%</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ============ SUMBER TOMBOL ============ --}}
@if(!empty($perSumber))
<div class="card">
    <h3 style="margin-top:0">Tombol mana yang diklik</h3>
    <div style="display:flex;gap:26px;flex-wrap:wrap">
        @foreach($perSumber as $src => $jml)
            <div>
                <div style="font-size:1.5rem;font-weight:700">{{ number_format($jml) }}</div>
                <div class="muted" style="font-size:.8rem">
                    @if($src === 'float') tombol mengambang
                    @elseif($src === 'nav') menu atas
                    @elseif($src === 'inline') dalam halaman
                    @else lainnya @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

{{-- ============ TOP KOTA / LAYANAN ============ --}}
<div class="row" style="gap:16px;align-items:flex-start;flex-wrap:wrap">
    @foreach([['Kota paling banyak', $topKota], ['Layanan paling diminati', $topLayanan]] as [$judul, $data])
        @if(!empty($data))
        <div class="card" style="flex:1;min-width:280px">
            <h3 style="margin-top:0">{{ $judul }}</h3>
            <table style="width:100%;border-collapse:collapse;font-size:.88rem">
                <tbody>
                @foreach($data as $nama => $jml)
                    <tr style="border-bottom:1px solid var(--border)">
                        <td style="padding:5px 0;text-transform:capitalize">{{ $nama }}</td>
                        <td style="width:60px;text-align:right;font-weight:600">{{ number_format($jml) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    @endforeach
</div>

{{-- ============ TOP HALAMAN ============ --}}
@if(!empty($topHalaman))
<div class="card">
    <h3 style="margin-top:0">Halaman penghasil lead terbanyak</h3>
    <p class="muted" style="margin:0 0 8px;font-size:.85rem">Ini pola yang terbukti — perbanyak halaman serupa.</p>
    <table style="width:100%;border-collapse:collapse;font-size:.88rem">
        <thead><tr style="text-align:left;border-bottom:1px solid var(--border)">
            <th style="padding:6px 0">Halaman</th><th style="width:70px;text-align:right">Klik</th>
        </tr></thead>
        <tbody>
        @foreach($topHalaman as $path => $jml)
            <tr style="border-bottom:1px solid var(--border)">
                <td style="padding:6px 0"><a href="{{ url($path) }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($path, 60) }}</a></td>
                <td style="text-align:right;font-weight:600">{{ number_format($jml) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

@endif
@endif
@endsection
