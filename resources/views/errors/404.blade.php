{{--
    404 — memakai layout yang sama (chrome + footer hub link identik), sehingga
    pengunjung tetap punya jalan ke hub layanan/kota. Semua variabel layout
    punya default, jadi aman dirender di luar controller biasa.
--}}
@extends('layouts.site')

@section('content')
@php
    $s = fn ($k, $d = '') => \App\Models\Setting::get($k, $d);
    $brand = $s('brand_name', '');
    $waNum  = \App\Support\WaLink::numberFor(request()->getPathInfo() ?: '/404');
    $waLink = \App\Support\WaLink::generic($brand);
@endphp
<header class="cegu-hero">
    <div class="in" style="text-align:center">
        <span class="cegu-eyebrow">404</span>
        <h1>Halaman tidak ditemukan</h1>
        <p class="lead" style="margin-left:auto;margin-right:auto;max-width:560px">
            Maaf, alamat yang Anda tuju tidak tersedia atau sudah dipindahkan.
            Silakan kembali ke beranda atau pilih layanan &amp; kota di bawah.
        </p>
        <div class="cegu-hero-cta" style="justify-content:center">
            <a class="cegu-btn light" href="{{ url('/') }}">Kembali ke Beranda</a>
            @if($waNum)<a class="cegu-btn" href="{{ $waLink }}" target="_blank" rel="noopener nofollow">💬 Hubungi Kami</a>@endif
        </div>
    </div>
</header>
@endsection
