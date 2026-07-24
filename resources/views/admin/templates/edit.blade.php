@extends('admin.layout')
@section('title', $isNew ? 'Template Baru' : 'Edit: '.$template->name)

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/theme/dracula.min.css">
    <style>
        .CodeMirror{height:420px;border:1px solid var(--border);border-radius:8px;font-size:.85rem}
        .editor-tabs{display:flex;gap:6px;margin-bottom:8px}
        .editor-tabs button{background:#e2e8f0;border:0;border-radius:6px 6px 0 0;padding:7px 14px;cursor:pointer;font-weight:600;font-size:.82rem}
        .editor-tabs button.active{background:var(--p);color:#fff}
        .pane{display:none}.pane.active{display:block}
        .tok{background:var(--p-soft);color:var(--p);padding:1px 6px;border-radius:5px;margin:2px;display:inline-block;font-size:.78rem;cursor:pointer}
    </style>
@endpush

@section('content')
@if(session('periksa_template'))
@php
    $pr = collect(session('periksa_template'));
    $nErr = $pr->where('level', 'error')->count();
    $nWarn = $pr->where('level', 'warn')->count();
@endphp
<div class="card {{ $nErr ? 'a-bad' : ($nWarn ? 'a-warn' : 'a-info') }}">
    <h3 style="margin-top:0">Hasil Pemeriksaan Template</h3>
    <ul style="margin:8px 0 0;padding-left:20px;line-height:1.8;font-size:.9rem">
        @foreach($pr as $item)
            <li class="{{ $item['level'] === 'error' ? 't-bad' : ($item['level'] === 'warn' ? 't-warn' : 'muted') }}">
                {{ $item['pesan'] }}
            </li>
        @endforeach
    </ul>
</div>
@endif

    <form method="POST" action="{{ $isNew ? route('admin.templates.store') : route('admin.templates.update', $template) }}" id="tplForm">
        @csrf
        @unless($isNew) @method('PUT') @endunless

        <div class="card">
            <div class="row">
                <div style="flex:1">
                    <label>Jenis Template</label>
                    <select name="type" style="margin-bottom:10px">
                        @foreach(\App\Models\Template::TYPES as $tv => $tl)
                            <option value="{{ $tv }}" @selected(old('type', $template->type ?? \App\Models\Template::TYPE_SALESPAGE) === $tv)>{{ $tl }}</option>
                        @endforeach
                    </select>
                    <label>Nama Template</label>
                    <input name="name" value="{{ old('name', $template->name) }}" required>
                </div>
                <div style="align-self:flex-end" class="row">
                    <label class="row" style="margin:0;gap:6px"><input type="checkbox" name="activate" value="1" style="width:auto" {{ $template->is_active ? 'checked' : '' }}> Set sebagai aktif</label>
                    <button class="btn" type="submit">Simpan</button>
                    <button class="btn gray" type="button" onclick="preview()">Preview</button>
                </div>
            </div>
        </div>

        <details class="card a-info">
            <summary style="cursor:pointer;font-weight:600">Daftar token tersedia</summary>
            <div style="margin-top:12px;font-size:.88rem;line-height:1.9">
                @foreach(['hero','wa','breadcrumb','intro','cta','about','summary','usp_list','pain_point_list','solusi_list','testimoni_list','faq','internal_links','layanan','kelurahan','kecamatan','kota','brand','year','katalog_layanan','kredensial','daftar_kota','daftar_layanan'] as $tok)
                    <code class="tok" onclick="navigator.clipboard.writeText('{{ '{{'.$tok.'}}' }}')">{{ '{{'.$tok.'}}' }}</code>
                @endforeach
            </div>
        </details>

        <div class="card">
            <div class="editor-tabs">
                <button type="button" class="active" data-pane="html">HTML / Blade</button>
                <button type="button" data-pane="css">CSS</button>
                <button type="button" data-pane="js">JavaScript</button>
            </div>
            <div class="pane active" id="pane-html"><textarea name="content" id="ed-html">{{ old('content', $template->content) }}</textarea></div>
            <div class="pane" id="pane-css"><textarea name="css" id="ed-css">{{ old('css', $template->css) }}</textarea></div>
            <div class="pane" id="pane-js"><textarea name="js" id="ed-js">{{ old('js', $template->js) }}</textarea></div>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.templates.preview') }}" target="_blank" id="previewForm">
        @csrf
        <input type="hidden" name="type" id="pv-type">
        <input type="hidden" name="content" id="pv-content">
        <input type="hidden" name="css" id="pv-css">
        <input type="hidden" name="js" id="pv-js">
    </form>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/xml/xml.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/javascript/javascript.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/css/css.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/htmlmixed/htmlmixed.min.js"></script>
    <script>
        const opt = (mode) => ({mode, theme:'dracula', lineNumbers:true, lineWrapping:true, indentUnit:2});
        const eHtml = CodeMirror.fromTextArea(document.getElementById('ed-html'), opt('htmlmixed'));
        const eCss  = CodeMirror.fromTextArea(document.getElementById('ed-css'),  opt('css'));
        const eJs   = CodeMirror.fromTextArea(document.getElementById('ed-js'),   opt('javascript'));

        document.querySelectorAll('.editor-tabs button').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.editor-tabs button').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.pane').forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById('pane-' + btn.dataset.pane).classList.add('active');
                [eHtml,eCss,eJs].forEach(e => e.refresh());
            });
        });

        document.getElementById('tplForm').addEventListener('submit', () => {
            eHtml.save(); eCss.save(); eJs.save();
        });

        function preview(){
            document.getElementById('pv-type').value = document.querySelector('select[name=\"type\"]').value;
            document.getElementById('pv-content').value = eHtml.getValue();
            document.getElementById('pv-css').value = eCss.getValue();
            document.getElementById('pv-js').value = eJs.getValue();
            document.getElementById('previewForm').submit();
        }
    </script>
@endpush
