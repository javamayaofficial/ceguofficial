@extends('admin.layout')
@section('title', 'Variasi Konten Salespage')

@section('content')
    <div class="card a-ok">
        <h3 style="margin-top:0">Paket Konten Awal — untuk jenis bisnis apa pun</h3>
        <p class="muted" style="margin:4px 0 10px">Mesin ini universal: jasa, herbal, properti, pendidikan, dan lainnya. Pilih paket yang paling dekat dengan bisnis Anda untuk mengisi stok kalimat + FAQ awal dalam sekali klik (variasi lama tidak dihapus). Setelah dimuat, sesuaikan dan perbanyak sesuai brand Anda.</p>
        <form method="POST" action="{{ route('admin.content.pack') }}" class="row" style="gap:10px;align-items:flex-end">
            @csrf
            <div><label style="margin:0">Jenis bisnis</label>
                <select name="pack" required>
                    @foreach($packs as $slug => $label)
                        <option value="{{ $slug }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn green" onclick="return confirm('Muat paket ini? Variasi & FAQ baru akan ditambahkan (yang lama tetap ada).')">Muat Paket</button>
        </form>
        <hr style="border:0;border-top:1px solid var(--border);margin:14px 0">
        <p class="muted" style="margin:0 0 8px"><strong>Atau import massal dari CSV</strong> — tulis ratusan variasi di Google Sheets, kolom: <code>section,content,weight</code> (weight opsional). Section valid: hero, intro, pain_point, solusi, usp, testimoni, cta, about, summary_open, summary_bridge, summary_close, summary_filler. Duplikat otomatis dilewati.</p>
        <form method="POST" action="{{ route('admin.content.import') }}" enctype="multipart/form-data" class="row" style="align-items:flex-end;gap:10px">
            @csrf
            <div><input type="file" name="csv" accept=".csv,.txt" required></div>
            <button class="btn">⬆ Import Variasi CSV</button>
        </form>
    </div>

    <div class="card a-p">
        <h3 style="margin-top:0">🤖 Isi Otomatis dengan AI — sampai semua indikator hijau</h3>
        <p class="muted" style="margin:4px 0 10px">AI akan menuliskan variasi kalimat (hero, intro, USP, testimoni, dst.) + FAQ sampai memenuhi target minimum, membuang duplikat otomatis. Hasil berupa kalimat pool ber-token (<code>@{{layanan}}</code>, <code>@{{kota}}</code>) — mesin lalu mengombinasikannya jadi jutaan halaman tanpa biaya AI tambahan. Konfigurasi kunci di <code>.env</code> (AI_DRIVER, AI_API_KEY, AI_MODEL).</p>

        @error('ai')<p style="color:var(--red);margin:4px 0">{{ $message }}</p>@enderror

        <form method="POST" action="{{ route('admin.content.ai') }}" class="row" style="gap:12px;align-items:flex-end;flex-wrap:wrap">
            @csrf
            <div style="flex:1;min-width:220px">
                <label style="margin:0">Jenis usaha / niche <span style="color:var(--red)">*</span></label>
                <input type="text" name="business" required placeholder="mis. les privat, jasa service AC, herbal, agen properti" value="{{ old('business') }}">
            </div>
            <div style="flex:1;min-width:220px">
                <label style="margin:0">Kata kunci / layanan utama (opsional)</label>
                <input type="text" name="keywords" placeholder="mis. matematika, fisika, kimia" value="{{ old('keywords') }}">
            </div>
            <div style="min-width:150px">
                <label style="margin:0">Gaya bahasa</label>
                <input type="text" name="tone" placeholder="ramah & meyakinkan" value="{{ old('tone') }}">
            </div>
            <div style="min-width:150px">
                <label style="margin:0">Kekayaan pool</label>
                <select name="multiplier">
                    <option value="1">Cukup untuk hijau (1×)</option>
                    <option value="1.5">Lebih kaya (1.5×)</option>
                    <option value="2">Sangat kaya (2×)</option>
                </select>
            </div>
            <label style="display:flex;align-items:center;gap:6px;margin:0"><input type="checkbox" name="include_faq" value="1" checked> Ikut isi FAQ</label>
            <button class="btn green" onclick="return confirm('Mulai isi otomatis dengan AI? Pastikan worker antrian (queue:work) berjalan.')">✨ Isi Otomatis</button>
        </form>

        <div id="ai-progress" style="margin-top:12px;display:none">
            <div style="height:10px;background:var(--border);border-radius:6px;overflow:hidden">
                <div id="ai-bar" style="height:100%;width:0;background:var(--p);transition:width .4s"></div>
            </div>
            <p id="ai-msg" class="muted" style="margin:8px 0 0"></p>
        </div>

        <script>
        (function () {
            var box = document.getElementById('ai-progress'),
                bar = document.getElementById('ai-bar'),
                msg = document.getElementById('ai-msg'),
                url = "{{ route('admin.content.ai.status') }}",
                timer = null;

            function targetsCount() { return {{ count(\App\Services\ContentHealthService::TARGETS) + 1 }}; }

            function poll() {
                fetch(url, {headers: {'Accept': 'application/json'}})
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        if (d.status === 'running' || d.status === 'done' || d.status === 'error') {
                            box.style.display = 'block';
                            var done = Object.keys(d.sections || {}).length + ((d.faq && d.faq.target) ? 1 : 0);
                            bar.style.width = Math.min(100, Math.round(done / targetsCount() * 100)) + '%';
                            msg.textContent = d.message || '';
                        }
                        if (d.status === 'done' || d.status === 'error') {
                            bar.style.width = '100%';
                            if (d.status === 'error') { bar.style.background = '#c0392b'; }
                            clearInterval(timer);
                            // Muat ulang untuk melihat indikator kesehatan terbaru.
                            setTimeout(function () { location.reload(); }, 2500);
                        }
                    })
                    .catch(function () {});
            }
            timer = setInterval(poll, 3000);
            poll();
        })();
        </script>
    </div>

    <div class="card">
        <p class="muted" style="margin-top:0">Tiap halaman memilih variasi secara <strong>deterministik</strong> dari pool ini (Formula Kombinasi PDF 2). Makin banyak variasi → makin banyak halaman unik. Token seperti <code>@{{layanan}}</code>, <code>@{{kelurahan}}</code> akan diganti otomatis.</p>
        <div class="row">
            @foreach($sections as $s)
                <a class="btn sm {{ $s === $section ? '' : 'ghost' }}" href="{{ route('admin.content.index', ['section' => $s]) }}">
                    {{ ucfirst(str_replace('_',' ',$s)) }} <span class="muted">({{ $counts[$s] ?? 0 }})</span>
                </a>
            @endforeach
        </div>
    </div>

    <div class="card">
        <h3 style="margin-top:0">Tambah variasi: <em>{{ ucfirst(str_replace('_',' ',$section)) }}</em></h3>
        <form method="POST" action="{{ route('admin.content.store') }}">
            @csrf
            <input type="hidden" name="section" value="{{ $section }}">
            <textarea name="content" rows="2" placeholder="Mis. Les Privat @{{layanan}} di @{{kelurahan}}, Tutor Datang ke Rumah" required></textarea>
            <div class="row" style="margin-top:8px">
                <div><label style="margin:0">Bobot</label><input type="number" name="weight" value="1" min="1" max="100" style="width:90px"></div>
                <button class="btn green" style="align-self:flex-end">+ Tambah</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top:0">Daftar variasi ({{ $blocks->count() }})</h3>
        @forelse($blocks as $block)
            <form method="POST" action="{{ route('admin.content.update', $block) }}" class="row" style="border-bottom:1px solid var(--border);padding:10px 0;align-items:flex-start">
                @csrf @method('PUT')
                <textarea name="content" rows="2" style="flex:1">{{ $block->content }}</textarea>
                <div style="width:80px"><label style="margin:0">Bobot</label><input type="number" name="weight" value="{{ $block->weight }}" min="1" max="100"></div>
                <label class="row" style="margin-top:24px;gap:5px"><input type="checkbox" name="is_active" value="1" style="width:auto" {{ $block->is_active ? 'checked':'' }}>aktif</label>
                <div style="margin-top:18px" class="row">
                    <button class="btn sm">Simpan</button>
                </div>
                <button form="del-{{ $block->id }}" class="btn red sm" style="margin-top:18px" onclick="return confirm('Hapus variasi ini?')">Hapus</button>
            </form>
            <form id="del-{{ $block->id }}" method="POST" action="{{ route('admin.content.destroy', $block) }}">@csrf @method('DELETE')</form>
        @empty
            <p class="muted">Belum ada variasi untuk section ini.</p>
        @endforelse
    </div>
@endsection
