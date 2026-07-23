{{--
    410 Gone — halaman yang pernah tayang lalu dicabut. Beri sinyal jelas ke
    pengunjung + jalan ke hub. (Google memakai status 410 untuk mencabut cepat.)
--}}
@extends('layouts.site')

@section('content')
<header class="cegu-hero">
    <div class="in" style="text-align:center">
        <span class="cegu-eyebrow">410</span>
        <h1>Halaman sudah tidak tersedia</h1>
        <p class="lead" style="margin-left:auto;margin-right:auto;max-width:560px">
            Halaman ini telah dihapus secara permanen. Silakan jelajahi layanan
            dan wilayah lain melalui beranda atau tautan di bawah.
        </p>
        <div class="cegu-hero-cta" style="justify-content:center">
            <a class="cegu-btn light" href="{{ url('/') }}">Kembali ke Beranda</a>
        </div>
    </div>
</header>
@endsection
