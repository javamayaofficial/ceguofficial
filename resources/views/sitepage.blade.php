{{--
    Halaman statis (Tentang Kami, Layanan, Kontak, dll).
    Memakai layout yang sama dengan salespage & beranda agar chrome konsisten.
--}}
@extends('layouts.site')

@section('content')
    <header class="cegu-hero">
        <div class="in">
            <nav class="cegu-breadcrumb" aria-label="Breadcrumb">
                @foreach($breadcrumb as $i => $crumb)
                    @if($i === count($breadcrumb) - 1)
                        <span aria-current="page">{{ $crumb['label'] }}</span>
                    @else
                        <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a> <span class="sep">/</span>
                    @endif
                @endforeach
            </nav>
            <h1>{{ $page->title }}</h1>
            @if($page->meta_description)
                <p class="lead" style="padding-bottom:40px">{{ $page->meta_description }}</p>
            @else
                <div style="padding-bottom:40px"></div>
            @endif
        </div>
    </header>

    @if($heroImage)
    <section class="cegu-section" style="padding-bottom:0">
        <div class="in">
            <img src="{{ $heroImage }}" alt="{{ $page->title }}"
                 class="cegu-section-img" loading="lazy"
                 style="width:100%;height:auto;border-radius:var(--radius)">
        </div>
    </section>
    @endif

    <section class="cegu-section">
        <div class="in cegu-about">
            <div class="cegu-summary">{!! $isi !!}</div>
        </div>
    </section>

    {{-- Galeri otomatis: tampil bila admin tidak menyisipkan token gambar
         sendiri di dalam konten, agar gambar yang diunggah tetap terpakai. --}}
    @if(!empty($gambarSisa))
    <section class="cegu-section alt" aria-labelledby="h-galeri">
        <div class="in">
            <div class="cegu-head">
                <h2 id="h-galeri">Galeri</h2>
            </div>
            <div class="cegu-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
                @foreach($gambarSisa as $g)
                    <div style="aspect-ratio:4/3;overflow:hidden;border-radius:var(--radius)">
                        <img src="{{ $g['url'] }}" alt="{{ $g['alt'] }}" loading="lazy"
                             style="width:100%;height:100%;object-fit:cover">
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif
@endsection
