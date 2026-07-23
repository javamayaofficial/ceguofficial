@extends('admin.layout')
@section('title', 'Pengaturan')

@section('content')
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf @method('PUT')

        <div class="card">
            <h3 style="margin-top:0">WhatsApp (CTA Lead)</h3>
            <label>Nomor WhatsApp <span class="muted">(format internasional, mis. 6281234567890)</span></label>
            <textarea name="whatsapp_number" rows="3" placeholder="Satu nomor per baris untuk ROTATOR (beban lead terbagi merata):&#10;6281234567890&#10;6281234567891&#10;6281234567892">{{ $settings['whatsapp_number'] ?? '' }}</textarea>
            <p class="muted" style="margin:4px 0 0;font-size:.85rem">💡 Isi <strong>beberapa nomor (satu per baris)</strong> untuk mengaktifkan rotator — tiap halaman diarahkan ke nomor berbeda secara merata, sehingga tidak ada satu nomor yang kebanjiran lead atau kena limit WhatsApp. Satu nomor saja juga tetap berfungsi normal.</p>
            <label>Pesan otomatis <span class="muted">(boleh pakai token @{{layanan}}, @{{kelurahan}})</span></label>
            <textarea name="whatsapp_message" rows="2">{{ $settings['whatsapp_message'] ?? '' }}</textarea>
        </div>

        <div class="card">
            <h3 style="margin-top:0">Logo (Navbar &amp; Footer)</h3>
            <p class="muted" style="margin-top:0">Logo brand yang tampil di <strong>menu atas &amp; footer beranda</strong>. Isi URL logo bisnis Anda. Kalau dikosongkan, akan memakai Gambar Hero sebagai fallback.</p>
            <label>URL Logo</label>
            <input name="logo_image" id="logo_image" value="{{ $settings['logo_image'] ?? '' }}" oninput="document.getElementById('logo_prev').src=this.value" placeholder="https://ik.imagekit.io/.../logo.png">
            <div style="margin-top:12px">
                <span class="muted" style="font-size:.82rem">Pratinjau:</span><br>
                <img id="logo_prev" src="{{ $settings['logo_image'] ?? '' }}" alt="Pratinjau logo"
                     style="margin-top:6px;max-height:60px;border-radius:8px;border:1px solid var(--border)"
                     onerror="this.style.opacity=.3">
            </div>
        </div>

        <div class="card">
            <h3 style="margin-top:0">Gambar Hero</h3>
            <p class="muted" style="margin-top:0">Gambar besar di <strong>hero (bagian atas) setiap salespage &amp; beranda</strong> — cocok diisi <strong>foto suasana belajar</strong> (bukan logo). Boleh URL penuh atau path server. <strong>Alt teks otomatis memakai judul halaman</strong> (ramah SEO). Kosongkan untuk menyembunyikan.</p>
            <label>URL / Path Gambar Hero</label>
            <input name="hero_image" id="hero_image" value="{{ $settings['hero_image'] ?? '' }}" oninput="document.getElementById('hero_prev').src=this.value||'{{ asset('images/hero-default.svg') }}'">
            <div style="margin-top:12px">
                <span class="muted" style="font-size:.82rem">Pratinjau:</span><br>
                <img id="hero_prev" src="{{ $settings['hero_image'] ?? asset('images/hero-default.svg') }}" alt="Pratinjau gambar hero"
                     style="margin-top:6px;max-width:320px;max-height:180px;border-radius:10px;border:1px solid var(--border);object-fit:cover"
                     onerror="this.style.opacity=.3;this.alt='Gambar tidak dapat dimuat'">
            </div>
        </div>

        <div class="card">
            <h3 style="margin-top:0">Gambar Section (opsional)</h3>
            <p class="muted" style="margin-top:0">Tampil di dalam badan salespage untuk membuatnya lebih menarik. Isi URL gambar (mis. dari ImageKit); <strong>kosongkan untuk menyembunyikan</strong> — section tetap tampil rapi tanpa gambar. Alt teks otomatis memuat layanan &amp; lokasi (ramah SEO). Gambar sama berlaku di semua halaman.</p>
            <div style="display:flex;flex-direction:column;gap:14px">
                <div>
                    <label>Gambar Section "Solusi" <span class="muted">(mis. suasana tutor mengajar)</span></label>
                    <input name="image_solusi" value="{{ $settings['image_solusi'] ?? '' }}" placeholder="https://ik.imagekit.io/.../solusi.jpg">
                </div>
                <div>
                    <label>Gambar Section "Keunggulan" <span class="muted">(mis. tim / kepercayaan)</span></label>
                    <input name="image_keunggulan" value="{{ $settings['image_keunggulan'] ?? '' }}" placeholder="https://ik.imagekit.io/.../keunggulan.jpg">
                </div>
                <div>
                    <label>Gambar Section "Tentang" <span class="muted">(mis. brand / kantor)</span></label>
                    <input name="image_tentang" value="{{ $settings['image_tentang'] ?? '' }}" placeholder="https://ik.imagekit.io/.../tentang.jpg">
                </div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-top:0">Beranda — Angka Kepercayaan</h3>
            <p class="muted" style="margin-top:0">Tiga kotak angka di hero beranda. Gunakan klaim yang jujur & bisa dipertanggungjawabkan.</p>
            <div class="row" style="gap:10px;flex-wrap:wrap">
                <div style="flex:1;min-width:140px"><label>Angka 1</label><input name="home_stat1_num" value="{{ $settings['home_stat1_num'] ?? '' }}" placeholder="Ratusan"></div>
                <div style="flex:2;min-width:180px"><label>Label 1</label><input name="home_stat1_label" value="{{ $settings['home_stat1_label'] ?? '' }}" placeholder="Siswa Terbantu"></div>
            </div>
            <div class="row" style="gap:10px;flex-wrap:wrap;margin-top:8px">
                <div style="flex:1;min-width:140px"><label>Angka 2</label><input name="home_stat2_num" value="{{ $settings['home_stat2_num'] ?? '' }}" placeholder="Puluhan"></div>
                <div style="flex:2;min-width:180px"><label>Label 2</label><input name="home_stat2_label" value="{{ $settings['home_stat2_label'] ?? '' }}" placeholder="Tutor Berpengalaman"></div>
            </div>
            <div class="row" style="gap:10px;flex-wrap:wrap;margin-top:8px">
                <div style="flex:1;min-width:140px"><label>Angka 3</label><input name="home_stat3_num" value="{{ $settings['home_stat3_num'] ?? '' }}" placeholder="Semua"></div>
                <div style="flex:2;min-width:180px"><label>Label 3</label><input name="home_stat3_label" value="{{ $settings['home_stat3_label'] ?? '' }}" placeholder="Jenjang TK–SMA"></div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-top:0">Beranda — Galeri Success Story</h3>
            <p class="muted" style="margin-top:0">Hingga 6 foto (mis. alumni/siswa). Kosongkan yang tidak dipakai — section muncul hanya bila ada minimal 1 foto. Upload ke ImageKit, tempel URL-nya.</p>
            <div style="display:flex;flex-direction:column;gap:8px">
                @for($i=1;$i<=6;$i++)
                    <input name="home_gallery{{ $i }}" value="{{ $settings['home_gallery'.$i] ?? '' }}" placeholder="URL foto {{ $i }} (opsional)">
                @endfor
            </div>
        </div>

        <div class="card">
            <h3 style="margin-top:0">Beranda — Logo Sekolah/Universitas</h3>
            <p class="muted" style="margin-top:0">Satu gambar strip berisi logo-logo sekolah/kampus (badge kepercayaan). Kosongkan untuk menyembunyikan section ini.</p>
            <label>URL Gambar Logo Sekolah</label>
            <input name="home_schools_img" value="{{ $settings['home_schools_img'] ?? '' }}" placeholder="https://ik.imagekit.io/.../sekolah.png">
        </div>

        <div class="card">
            <h3 style="margin-top:0">Beranda — Profil Pemilik / Pendiri</h3>
            <p class="muted" style="margin-top:0">Foto + cerita singkat pemilik untuk memberi wajah pada bisnis. Kosongkan foto & nama untuk menyembunyikan section.</p>
            <label>Foto Pemilik (URL)</label>
            <input name="home_owner_img" value="{{ $settings['home_owner_img'] ?? '' }}" placeholder="https://ik.imagekit.io/.../owner.jpg">
            <div class="row" style="gap:10px;flex-wrap:wrap;margin-top:8px">
                <div style="flex:1;min-width:180px"><label>Nama</label><input name="home_owner_name" value="{{ $settings['home_owner_name'] ?? '' }}" placeholder="Kak Bekky"></div>
                <div style="flex:1;min-width:180px"><label>Jabatan</label><input name="home_owner_role" value="{{ $settings['home_owner_role'] ?? '' }}" placeholder="mis. Owner / Pemilik"></div>
            </div>
            <label style="margin-top:8px">Cerita Singkat</label>
            <textarea name="home_owner_desc" rows="3" placeholder="Kalimat singkat tentang pemilik & misi bisnis...">{{ $settings['home_owner_desc'] ?? '' }}</textarea>
        </div>

        <div class="card">
            <h3 style="margin-top:0">Beranda — Kontak &amp; Alamat</h3>
            <p class="muted" style="margin-top:0">Muncul sebagai section "Hubungi Kami" di beranda. Kosongkan semua untuk menyembunyikan section.</p>
            <label>Alamat Kantor</label>
            <input name="contact_address" value="{{ $settings['contact_address'] ?? '' }}" placeholder="Permata Depok Sektor Safir M8 No.20, Citayam, Depok, Jabar">
            <div class="row" style="gap:10px;flex-wrap:wrap;margin-top:8px">
                <div style="flex:1;min-width:180px"><label>Telepon</label><input name="contact_phone" value="{{ $settings['contact_phone'] ?? '' }}" placeholder="+62 878-8770-3026"></div>
                <div style="flex:1;min-width:180px"><label>Email</label><input name="contact_email" value="{{ $settings['contact_email'] ?? '' }}" placeholder="info@cegu.co.id"></div>
            </div>
            <label style="margin-top:8px">Jam Operasional</label>
            <input name="contact_hours" value="{{ $settings['contact_hours'] ?? '' }}" placeholder="09.00 – 17.00 WIB (Tutup akhir pekan)">
        </div>

        <div class="card">
            <h3 style="margin-top:0">Brand &amp; Schema Organization</h3>
            <div class="row">
                <div style="flex:1"><label>Nama Brand</label><input name="brand_name" value="{{ $settings['brand_name'] ?? '' }}" placeholder="Nama bisnis Anda"></div>
                <div style="flex:1"><label>Tagline / Slogan <span class="muted">(tampil di beranda & footer — sesuaikan dengan bisnis Anda)</span></label><input name="tagline" value="{{ $settings['tagline'] ?? '' }}" placeholder="Mis. Herbal asli, kirim cepat ke seluruh kota"></div>
                <div style="width:220px"><label>Kode Tema <span class="muted">(sidik jari unik situs ini — ganti untuk mengocok ulang tampilan)</span></label><input name="theme_prefix" value="{{ $settings['theme_prefix'] ?? '' }}" placeholder="mis. qx7" pattern="[a-z][a-z0-9]{1,7}"></div>
                <div style="flex:1"><label>Organization Name</label><input name="organization_name" value="{{ $settings['organization_name'] ?? '' }}"></div>
            </div>
            <div class="row">
                <div style="flex:1"><label>Organization URL</label><input name="organization_url" value="{{ $settings['organization_url'] ?? '' }}"></div>
                <div style="flex:1"><label>Logo URL</label><input name="organization_logo" value="{{ $settings['organization_logo'] ?? '' }}"></div>
            </div>
        </div>

        <div class="card a-ok">
            <h3 style="margin-top:0">Gambar Salespage</h3>
            <p class="muted" style="margin-top:0">Tempel URL gambar. Berlaku untuk <strong>semua halaman</strong> situs ini — namun <strong>alt text-nya otomatis berbeda tiap halaman</strong> (menyebut layanan &amp; lokasi masing-masing), sehingga tidak dianggap duplikat.</p>
            @php
                $slotGambar = [
                    'hero_image' => 'Hero (paling atas)',
                    'image_keunggulan' => 'Section Keunggulan',
                    'image_solusi' => 'Section Solusi',
                    'image_proses' => 'Section Proses / Cara Pesan',
                    'image_tentang' => 'Section Tentang',
                ];
            @endphp
            <div class="row" style="gap:12px;flex-wrap:wrap">
                @foreach($slotGambar as $sk => $slabel)
                    <div style="flex:1;min-width:250px">
                        <label style="margin:0">{{ $slabel }}</label>
                        <input name="{{ $sk }}" value="{{ $settings[$sk] ?? '' }}" placeholder="https://…/gambar.jpg">
                    </div>
                @endforeach
            </div>

            <h4 style="margin:16px 0 6px">Galeri (maksimal 6 gambar)</h4>
            <p class="muted" style="margin:0 0 8px;font-size:.85rem">Tampil lewat token <code>@{{galeri}}</code> di template. Tiap gambar mendapat alt berbeda otomatis.</p>
            <div class="row" style="gap:12px;flex-wrap:wrap">
                @for($gi = 1; $gi <= 6; $gi++)
                    <div style="flex:1;min-width:220px">
                        <label style="margin:0">Galeri {{ $gi }}</label>
                        <input name="image_galeri_{{ $gi }}" value="{{ $settings['image_galeri_' . $gi] ?? '' }}" placeholder="https://…">
                    </div>
                @endfor
            </div>
        </div>

        <div class="card a-p">
            <h3 style="margin-top:0">Warna Situs</h3>
            <p class="muted" style="margin-top:0">Mesin ini dipakai untuk banyak produk, jadi <strong>strukturnya seragam</strong> — yang membedakan tiap situs cukup warna, logo, dan gambar. Kosongkan untuk memakai warna bawaan.</p>
            <div class="row" style="gap:14px;flex-wrap:wrap">
                @foreach(($colorPalette ?? []) as $ck => $info)
                    <div style="min-width:190px">
                        <label style="margin:0">{{ $info[2] }}</label>
                        <div style="display:flex;gap:6px;align-items:center">
                            <input type="color" value="{{ $colorValues[$ck] ?? $info[1] }}"
                                   oninput="this.nextElementSibling.value=this.value" style="width:44px;height:38px;padding:2px">
                            <input name="{{ $ck }}" value="{{ $settings[$ck] ?? '' }}"
                                   placeholder="{{ $info[1] }}" style="flex:1;font-family:monospace">
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="muted" style="margin:10px 0 0;font-size:.85rem">Hanya kode HEX yang diterima (mis. <code>#2b8a99</code>). Setelah menyimpan, buka salespage dan tekan Ctrl+Shift+R untuk melihat perubahan.</p>
        </div>

        <div class="card a-info">
            <h3 style="margin-top:0">🔗 Integrasi Google (Search Console &amp; Analytics)</h3>
            <p class="muted" style="margin-top:0">Tempel kode dari Google di sini — otomatis dipasang di <code>&lt;head&gt;</code> semua halaman. Untuk Search Console, pilih metode <strong>"Tag HTML"</strong>, salin isinya ke kolom pertama (boleh tempel seluruh tag <code>&lt;meta&gt;</code>, sistem mengambil kodenya otomatis).</p>

            <label>Google Search Console — kode verifikasi <span class="muted">(metode Tag HTML)</span></label>
            <input name="google_site_verification" value="{{ $settings['google_site_verification'] ?? '' }}" placeholder='mis. AbCdEf123... atau tempel <meta name="google-site-verification" content="..."> penuh'>
            <p class="muted" style="margin:4px 0 0;font-size:.85rem">Alternatif: metode "File HTML" → unggah file <code>googleXXXX.html</code> dari Google ke folder <code>public/</code> di server. Metode DNS diatur di penyedia domain, bukan di sini.</p>

            <label style="margin-top:10px">Bing Webmaster — kode verifikasi <span class="muted">(opsional, melengkapi IndexNow)</span></label>
            <input name="bing_site_verification" value="{{ $settings['bing_site_verification'] ?? '' }}" placeholder='kode msvalidate.01, atau tempel tag <meta> penuh'>

            <div class="row" style="gap:10px;flex-wrap:wrap;margin-top:10px">
                <div style="flex:1;min-width:220px">
                    <label>Google Analytics 4 — Measurement ID</label>
                    <input name="google_analytics_id" value="{{ $settings['google_analytics_id'] ?? '' }}" placeholder="G-XXXXXXXXXX">
                    <p class="muted" style="margin:4px 0 0;font-size:.85rem">Otomatis memasang skrip gtag.js. Kosongkan untuk menonaktifkan.</p>
                </div>
                <div style="flex:1;min-width:220px">
                    <label>Google Tag Manager — Container ID</label>
                    <input name="gtm_id" value="{{ $settings['gtm_id'] ?? '' }}" placeholder="GTM-XXXXXXX">
                    <p class="muted" style="margin:4px 0 0;font-size:.85rem">Alternatif GA4 bila Anda pakai GTM. Isi salah satu saja.</p>
                </div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-top:0">Lanjutan</h3>
            <label class="row" style="gap:8px"><input type="checkbox" name="template_blade_enabled" value="1" style="width:auto" {{ ($settings['template_blade_enabled'] ?? '0')==='1'?'checked':'' }}>
                Aktifkan rendering Blade pada template <span class="muted">(untuk @@if/@@foreach — hanya admin terpercaya)</span></label>
        </div>

        <button class="btn">💾 Simpan Pengaturan</button>
    </form>
@endsection
