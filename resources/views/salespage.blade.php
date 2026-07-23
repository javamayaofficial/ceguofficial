{{--
    Salespage — hanya membungkus $body yang dihasilkan PageRenderer.
    Chrome (nav/footer/head/schema/CSS base/theme fingerprint) diatur di
    layouts.site sehingga IDENTIK dengan beranda dan halaman kategori.

    Variabel yang datang dari PageRenderer::render():
        page, template, body, css, js, seo, wa
--}}
@extends('layouts.site', [
    'seo' => $seo,
    'css' => $css,
    'js'  => $js,
    'wa'  => $wa,
])

@section('content')
    {!! $body !!}
@endsection
