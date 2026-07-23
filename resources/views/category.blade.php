{{--
    Halaman kategori (1–3 segmen URL) — memakai layout yang sama dengan
    beranda & salespage supaya chrome konsisten. Konten cuma breadcrumb +
    heading + grid link internal untuk memperkuat internal linking.
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
            <h1>{{ $title }}</h1>
            <p class="lead" style="padding-bottom:48px">{{ $heading }}</p>
        </div>
    </header>

    <section class="cegu-section">
        <div class="in">
            <div class="cegu-grid">
                @foreach($items as $item)
                    <a class="cegu-tile" style="text-decoration:none;color:inherit" href="{{ $item['url'] }}">
                        <h3 style="margin:0;font-size:1rem">{{ $item['label'] }} →</h3>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endsection
