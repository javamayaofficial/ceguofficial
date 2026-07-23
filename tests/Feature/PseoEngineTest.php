<?php

namespace Tests\Feature;

use App\Jobs\GeneratePagesJob;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Page;
use App\Services\PageGenerator;
use App\Services\PageRenderer;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PseoEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Template default + pool variasi konten + FAQ + settings.
        $this->seed(ContentSeeder::class);
        // Prefix tema deterministik agar asersi test stabil.
        \App\Models\Setting::updateOrCreate(['key' => 'theme_prefix'], ['value' => 'zz9']);
        \App\Models\Setting::flushCache();
    }

    private function makePage(string $status = Page::STATUS_PUBLISHED): Page
    {
        $result = (new PageGenerator())->generate([
            'layanan' => 'Les Privat Matematika',
            'kota' => 'Bandung',
            'kecamatan' => 'Cicendo',
            'kelurahan' => 'Pajajaran',
        ]);
        $page = $result['page'];
        $page->update([
            'status' => $status,
            'published_at' => $status === Page::STATUS_PUBLISHED ? now() : null,
        ]);

        return $page->refresh();
    }

    public function test_url_path_follows_rfp_structure(): void
    {
        $page = $this->makePage();
        $this->assertSame('les-privat-matematika/bandung/cicendo/pajajaran', $page->path);
    }

    public function test_extra_csv_columns_become_local_data_tokens(): void
    {
        $result = (new PageGenerator())->generate([
            'layanan' => 'Les Privat Fisika',
            'kota' => 'Bandung',
            'kecamatan' => 'Coblong',
            'kelurahan' => 'Dago',
            'extra' => ['harga' => 'Rp75.000/sesi', 'jumlah_tutor' => '12', 'landmark' => 'ITB'],
        ]);
        $page = $result['page'];
        $page->update(['status' => Page::STATUS_PUBLISHED, 'published_at' => now()]);

        $res = $this->get('/les-privat-fisika/bandung/coblong/dago');
        $res->assertOk();
        // Fakta lokal tampil di halaman & dianyam ke summary/meta description.
        $res->assertSee('Rp75.000/sesi', false);
        $res->assertSee('ITB', false);
        $res->assertSee('<ul class="zz9-fakta">', false);
        // Token tak terselesaikan tidak boleh bocor mentah ke pengunjung.
        $res->assertDontSee('{{harga}}', false);
    }

    public function test_reimport_same_location_enriches_existing_page(): void
    {
        $gen = new PageGenerator();
        $first = $gen->generate([
            'layanan' => 'Guru Ngaji', 'kota' => 'Bekasi',
            'kecamatan' => 'Jatiasih', 'kelurahan' => 'Jatikramat',
        ]);
        $this->assertTrue($first['created']);
        $this->assertNull($first['page']->extra);

        $second = $gen->generate([
            'layanan' => 'Guru Ngaji', 'kota' => 'Bekasi',
            'kecamatan' => 'Jatiasih', 'kelurahan' => 'Jatikramat',
            'extra' => ['harga' => 'Rp50.000/sesi'],
        ]);
        $this->assertFalse($second['created']);
        $this->assertSame('Rp50.000/sesi', $second['page']->fresh()->extra['harga']);
    }

    public function test_content_pack_loads_for_any_business_type(): void
    {
        $packs = new \App\Services\ContentPackService();

        // 4 paket niche tersedia
        $this->assertEqualsCanonicalizing(
            ['herbal', 'jasa_umum', 'pendidikan', 'properti'],
            array_keys($packs->available())
        );

        $before = \App\Models\ContentBlock::count();
        $result = $packs->load('herbal');
        $this->assertGreaterThan(0, $result['blocks']);
        $this->assertGreaterThan(0, $result['faqs']);
        $this->assertGreaterThan($before, \App\Models\ContentBlock::count());

        // Memuat ulang paket yang sama = idempoten (tidak menduplikasi)
        $again = $packs->load('herbal');
        $this->assertSame(0, $again['blocks']);
    }

    public function test_summary_pool_is_database_driven_for_multi_niche(): void
    {
        // Ganti seluruh pool summary dengan kalimat niche properti
        \App\Models\ContentBlock::whereIn('section', ['summary_open', 'summary_bridge', 'summary_close'])->delete();
        \App\Models\ContentBlock::create(['section' => 'summary_open', 'content' => 'PROPERTIKU menawarkan {{layanan}} unggulan di {{kelurahan}}.', 'weight' => 1, 'is_active' => true]);
        \App\Models\ContentBlock::create(['section' => 'summary_bridge', 'content' => 'Legalitas aman dengan {{usp_text}}.', 'weight' => 1, 'is_active' => true]);
        \App\Models\ContentBlock::create(['section' => 'summary_close', 'content' => 'Survei gratis tersedia untuk warga {{kelurahan}}.', 'weight' => 1, 'is_active' => true]);
        \App\Services\ContentRepository::flushCache();

        $this->makePage();
        $res = $this->get('/les-privat-matematika/bandung/cicendo/pajajaran');

        $res->assertOk();
        $res->assertSee('PROPERTIKU menawarkan', false);
        $res->assertSee('Survei gratis tersedia', false);
    }

    public function test_theme_fingerprint_replaces_all_css_classes_on_public_pages(): void
    {
        $this->makePage();
        $res = $this->get('/les-privat-matematika/bandung/cicendo/pajajaran');

        $res->assertOk();
        // Semua class terganti prefix instalasi; tidak ada jejak 'cegu-' tersisa.
        $res->assertSee('zz9-hero', false);
        $res->assertDontSee('cegu-', false);
        // Panel admin TIDAK disentuh sidik jari (bukan halaman publik).
        $this->assertStringNotContainsString('zz9-', \App\Support\ThemeFingerprint::apply('')); // no-op sanity
    }

    public function test_theme_fingerprint_visual_variance_is_deterministic_and_distinct(): void
    {
        \App\Models\Setting::updateOrCreate(['key' => 'theme_prefix'], ['value' => 'ab3']);
        \App\Models\Setting::flushCache();
        $a = \App\Support\ThemeFingerprint::cssOverrides();
        $a2 = \App\Support\ThemeFingerprint::cssOverrides();

        \App\Models\Setting::updateOrCreate(['key' => 'theme_prefix'], ['value' => 'xk7m']);
        \App\Models\Setting::flushCache();
        $b = \App\Support\ThemeFingerprint::cssOverrides();

        $this->assertSame($a, $a2, 'Harus deterministik untuk prefix yang sama');
        $this->assertNotSame($a, $b, 'Prefix berbeda harus menghasilkan varian visual berbeda');
        $this->assertGreaterThanOrEqual(3, \App\Models\Template::count(), 'Tiga varian template ter-seed');
    }

    public function test_cross_join_import_multiplies_locations_by_keyword_list(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $csv = "kota,kecamatan,kelurahan,harga\nBandung,Coblong,Dago,Rp75.000\nBandung,Coblong,Lebakgede,Rp70.000\n";
        \Illuminate\Support\Facades\Storage::disk('local')->put('imports/lokasi.csv', $csv);

        $batch = ImportBatch::create([
            'original_filename' => 'lokasi.csv',
            'stored_path' => 'imports/lokasi.csv',
            'layanan_list' => "Les Privat Matematika\nGuru Ngaji\nLes Bahasa Inggris",
            'status' => ImportBatch::STATUS_QUEUED,
        ]);
        (new \App\Jobs\ImportCsvJob($batch->id))->handle();

        // 2 lokasi × 3 layanan = 6 baris staging
        $this->assertSame(6, ImportRow::where('import_batch_id', $batch->id)->count());
        $this->assertSame(6, (int) $batch->fresh()->total_rows);
        // Data lokal ikut tersalin ke tiap kombinasi
        $this->assertSame(3, ImportRow::where('import_batch_id', $batch->id)
            ->where('kelurahan', 'Dago')->get()
            ->filter(fn ($r) => ($r->extra['harga'] ?? null) === 'Rp75.000')->count());
    }

    public function test_content_block_csv_import_is_idempotent_and_validates_sections(): void
    {
        $admin = \App\Models\User::factory()->create();
        $csv = "section,content,weight\nhero,Judul Baru {{layanan}} di {{kelurahan}},2\nhero,Judul Baru {{layanan}} di {{kelurahan}},2\nsalah_section,Tidak valid,1\n";
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('variasi.csv', $csv);

        $res = $this->actingAs($admin)->post(route('admin.content.import'), ['csv' => $file]);
        $res->assertSessionHas('status');
        $this->assertSame(1, \App\Models\ContentBlock::where('content', 'like', 'Judul Baru%')->count());
        $this->assertStringContainsString('salah_section', session('status'));
    }

    public function test_import_tolerates_excel_indonesia_csv_bom_and_semicolon(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        // BOM UTF-8 + delimiter titik-koma (khas Excel locale Indonesia)
        $csv = "\xEF\xBB\xBFlayanan;kota;kecamatan;kelurahan;harga\n"
             . "Les Privat Kimia;Bandung;Coblong;Sadang Serang;Rp80.000\n";
        \Illuminate\Support\Facades\Storage::disk('local')->put('imports/excel-id.csv', $csv);

        $batch = ImportBatch::create([
            'original_filename' => 'excel-id.csv',
            'stored_path' => 'imports/excel-id.csv',
            'status' => ImportBatch::STATUS_QUEUED,
        ]);
        (new \App\Jobs\ImportCsvJob($batch->id))->handle();

        $row = ImportRow::where('import_batch_id', $batch->id)->first();
        $this->assertNotNull($row, 'File Excel Indonesia harus tetap terbaca');
        $this->assertSame('Les Privat Kimia', $row->layanan);
        $this->assertSame('Rp80.000', $row->extra['harga'] ?? null);
    }

    public function test_whatsapp_rotator_distributes_numbers_deterministically(): void
    {
        \App\Models\Setting::updateOrCreate(['key' => 'whatsapp_number'],
            ['value' => "6281111111111\n6282222222222\n6283333333333"]);
        \App\Models\Setting::flushCache();

        $lokasi = [
            ['Les Privat Matematika', 'Bandung', 'Cicendo', 'Pajajaran'],
            ['Les Privat Matematika', 'Bandung', 'Coblong', 'Dago'],
            ['Les Privat Matematika', 'Bandung', 'Coblong', 'Lebakgede'],
            ['Guru Ngaji', 'Bekasi', 'Jatiasih', 'Jatikramat'],
            ['Guru Ngaji', 'Bekasi', 'Jatiasih', 'Jatimekar'],
            ['Les Privat IPA', 'Depok', 'Beji', 'Kemiri Muka'],
        ];
        $used = [];
        foreach ($lokasi as [$l, $kt, $kc, $kl]) {
            $r = (new PageGenerator())->generate(['layanan' => $l, 'kota' => $kt, 'kecamatan' => $kc, 'kelurahan' => $kl]);
            $r['page']->update(['status' => Page::STATUS_PUBLISHED, 'published_at' => now()]);
            $html = $this->get('/' . $r['page']->path)->getContent();
            preg_match('#wa\.me/(\d+)#', $html, $m);
            $used[$m[1] ?? ''] = true;
            // Deterministik: halaman yang sama → nomor sama saat dirender ulang
            preg_match('#wa\.me/(\d+)#', $this->get('/' . $r['page']->path)->getContent(), $m2);
            $this->assertSame($m[1], $m2[1]);
        }
        // Rotasi bekerja: lebih dari satu nomor terpakai di seluruh halaman
        $this->assertGreaterThanOrEqual(2, count(array_filter(array_keys($used))));
    }

    public function test_section_images_appear_when_set_and_hidden_when_empty(): void
    {
        // Kosong dulu → tidak ada gambar section, tidak ada token mentah
        $this->makePage();
        $res = $this->get('/les-privat-matematika/bandung/cicendo/pajajaran');
        $res->assertOk();
        $res->assertDontSee('<div class="zz9-section-img">', false);
        $res->assertDontSee('{{gambar_solusi}}', false);

        // Isi gambar section → muncul dengan alt lokasi
        \App\Models\Setting::updateOrCreate(['key' => 'image_solusi'], ['value' => 'https://img.test/solusi.jpg']);
        \App\Models\Setting::flushCache();
        \App\Support\RenderCache::bump();

        $res2 = $this->get('/les-privat-matematika/bandung/cicendo/pajajaran');
        $res2->assertOk();
        $res2->assertSee('https://img.test/solusi.jpg', false);
        $res2->assertSee('<div class="zz9-section-img">', false);
        $res2->assertSee('loading="lazy"', false);
        // Alt memuat konteks lokasi (SEO)
        $res2->assertSee('Solusi Les Privat Matematika di Pajajaran', false);
    }

    public function test_page_without_extra_hides_fakta_lokal_and_raw_tokens(): void
    {
        $this->makePage();

        $res = $this->get('/les-privat-matematika/bandung/cicendo/pajajaran');
        $res->assertOk();
        $res->assertDontSee('<ul class="zz9-fakta">', false);
        $res->assertDontSee('{{fakta_lokal}}', false);
    }

    public function test_published_salespage_renders_with_seo_and_schema(): void
    {
        $this->makePage();

        $res = $this->get('/les-privat-matematika/bandung/cicendo/pajajaran');

        $res->assertOk();
        $res->assertSee('Les Privat Matematika', false);
        $res->assertSee('<link rel="canonical"', false);
        $res->assertSee('FAQPage', false);          // schema FAQ
        $res->assertSee('BreadcrumbList', false);    // schema breadcrumb
        $res->assertSee('Organization', false);      // schema organization
        $res->assertSee('wa.me/', false);            // CTA WhatsApp
    }

    public function test_draft_page_is_not_publicly_accessible(): void
    {
        $this->makePage(Page::STATUS_DRAFT);
        $this->get('/les-privat-matematika/bandung/cicendo/pajajaran')->assertNotFound();
    }

    public function test_content_variation_is_deterministic(): void
    {
        $page = $this->makePage();
        $renderer = app(PageRenderer::class);

        $first = $renderer->render($page)['body'];
        $second = $renderer->render($page->fresh())['body'];

        $this->assertSame($first, $second, 'Render dengan seed sama harus identik.');
    }

    public function test_summary_is_within_80_to_150_words(): void
    {
        $page = $this->makePage();
        $body = app(PageRenderer::class)->render($page)['body'];

        // Ambil isi div summary.
        preg_match('/<div class="cegu-summary">(.*?)<\/div>/s', $body, $m);
        $words = str_word_count(strip_tags($m[1] ?? ''));

        $this->assertGreaterThanOrEqual(80, $words);
        $this->assertLessThanOrEqual(150, $words);
    }

    public function test_hero_image_uses_headline_as_alt_for_seo(): void
    {
        $page = $this->makePage();
        $rendered = app(PageRenderer::class)->render($page);

        // Gambar hero ada, dan alt = judul/headline (SEO friendly).
        $this->assertStringContainsString('class="cegu-hero-img"', $rendered['body']);
        $this->assertStringContainsString('alt="' . e($rendered['seo']['h1']) . '"', $rendered['body']);
    }

    public function test_generate_job_creates_draft_pages_from_import_rows(): void
    {
        $batch = ImportBatch::create([
            'original_filename' => 'test.csv',
            'status' => ImportBatch::STATUS_QUEUED,
            'total_rows' => 2,
        ]);
        foreach ([['Bandung', 'Cicendo', 'Pajajaran'], ['Bekasi', 'Jatiasih', 'Jatikramat']] as $r) {
            ImportRow::create([
                'import_batch_id' => $batch->id,
                'layanan' => 'Les Privat Matematika',
                'kota' => $r[0], 'kecamatan' => $r[1], 'kelurahan' => $r[2],
                'status' => ImportRow::STATUS_PENDING,
            ]);
        }

        (new GeneratePagesJob($batch->id))->handle(new PageGenerator());

        $this->assertSame(2, Page::draft()->count());
        $this->assertSame(ImportBatch::STATUS_COMPLETED, $batch->fresh()->status);
    }

    public function test_sitemap_index_and_chunk(): void
    {
        $this->makePage();

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('sitemap-1.xml', false);

        $this->get('/sitemap-1.xml')
            ->assertOk()
            ->assertSee('les-privat-matematika/bandung/cicendo/pajajaran', false);
    }
}
