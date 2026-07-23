<?php

use App\Http\Controllers\Admin\ContentBlockController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\AssistantController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\IndexingController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\KeywordController;
use App\Http\Controllers\Admin\QuickStartController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SitePageController;
use App\Http\Controllers\Admin\TemplateController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\LeadTrackController;
use App\Http\Controllers\SitemapController;
use App\Models\Service;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Beranda
|--------------------------------------------------------------------------
| Middleware ApplyThemeFingerprint dipasang di sini agar beranda ikut
| memakai prefix CSS yang SAMA dengan salespage — sehingga nav, footer,
| dan seluruh chrome tampil identik visualnya.
*/
Route::get('/', function () {
    $services = Service::where('is_active', true)
        ->whereHas('pages', fn ($q) => $q->published())
        ->orderBy('name')->get(['name', 'slug']);

    return view('home', compact('services'));
})
    ->middleware(\App\Http\Middleware\ApplyThemeFingerprint::class)
    ->name('home');

/*
|--------------------------------------------------------------------------
| Sitemap (RFP: /sitemap.xml + auto index, 50.000 URL/file)
|--------------------------------------------------------------------------
*/
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemap-{n}.xml', [SitemapController::class, 'chunk'])->whereNumber('n')->name('sitemap.chunk');
Route::get('/sitemap-statis.xml', [SitemapController::class, 'static'])->name('sitemap.static');

/*
|--------------------------------------------------------------------------
| Pelacakan lead (klik WhatsApp) — beacon dari halaman publik
|--------------------------------------------------------------------------
*/
Route::post('/track/wa', [LeadTrackController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('track.wa');

/*
|--------------------------------------------------------------------------
| robots.txt (dinamis → menunjuk ke sitemap) & verifikasi IndexNow
| Catatan: HAPUS file statis public/robots.txt agar route ini yang dipakai.
|--------------------------------------------------------------------------
*/
Route::get('/robots.txt', function () {
    $lines = [
        'User-agent: *',
        'Disallow: /admin',
        'Disallow: /login',
        'Allow: /',
        '',
        'Sitemap: ' . url('/sitemap.xml'),
    ];

    return response(implode("\n", $lines) . "\n", 200, ['Content-Type' => 'text/plain']);
})->name('robots');

// File verifikasi kepemilikan IndexNow: /indexnow.txt berisi key.
Route::get('/indexnow.txt', function () {
    $key = \App\Services\IndexNowService::key();
    abort_if($key === '', 404);

    return response($key, 200, ['Content-Type' => 'text/plain']);
})->name('indexnow.key');

/*
|--------------------------------------------------------------------------
| Auth (login admin)
|--------------------------------------------------------------------------
*/
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Panel Admin
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Template engine + editor + preview
    Route::get('templates', [TemplateController::class, 'index'])->name('templates.index');
    Route::get('templates/create', [TemplateController::class, 'create'])->name('templates.create');
    Route::post('templates', [TemplateController::class, 'store'])->name('templates.store');
    Route::get('templates/{template}/edit', [TemplateController::class, 'edit'])->name('templates.edit');
    Route::put('templates/{template}', [TemplateController::class, 'update'])->name('templates.update');
    Route::post('templates/{template}/activate', [TemplateController::class, 'activate'])->name('templates.activate');
    Route::post('templates/import-kerangka', [TemplateController::class, 'importKerangka'])->name('templates.import');
    Route::delete('templates/{template}', [TemplateController::class, 'destroy'])->name('templates.destroy');
    Route::post('templates/preview', [TemplateController::class, 'preview'])->name('templates.preview');

    // Variasi konten salespage (PDF 2)
    Route::get('content', [ContentBlockController::class, 'index'])->name('content.index');
    Route::post('content', [ContentBlockController::class, 'store'])->name('content.store');
    Route::post('content/pack', [ContentBlockController::class, 'loadPack'])->name('content.pack');
    Route::post('content/import', [ContentBlockController::class, 'importCsv'])->name('content.import');
    Route::post('content/ai-fill', [ContentBlockController::class, 'aiFill'])->name('content.ai');
    Route::get('content/ai-status', [ContentBlockController::class, 'aiStatus'])->name('content.ai.status');
    Route::put('content/{contentBlock}', [ContentBlockController::class, 'update'])->name('content.update');
    Route::delete('content/{contentBlock}', [ContentBlockController::class, 'destroy'])->name('content.destroy');

    // FAQ
    Route::get('faqs', [FaqController::class, 'index'])->name('faqs.index');
    Route::post('faqs', [FaqController::class, 'store'])->name('faqs.store');
    Route::post('faqs/import', [FaqController::class, 'importCsv'])->name('faqs.import');
    Route::put('faqs/{faq}', [FaqController::class, 'update'])->name('faqs.update');
    Route::delete('faqs/{faq}', [FaqController::class, 'destroy'])->name('faqs.destroy');

    // Generator keyword longtail (AI)
    Route::get('keywords', [KeywordController::class, 'index'])->name('keywords.index');
    Route::post('keywords', [KeywordController::class, 'generate'])->name('keywords.generate');

    // Halaman statis & menu navigasi
    Route::get('sitepages', [SitePageController::class, 'index'])->name('sitepages.index');
    Route::post('sitepages', [SitePageController::class, 'store'])->name('sitepages.store');
    Route::post('sitepages/preset', [SitePageController::class, 'loadPreset'])->name('sitepages.preset');
    Route::put('sitepages/{sitepage}', [SitePageController::class, 'update'])->name('sitepages.update');
    Route::delete('sitepages/{sitepage}', [SitePageController::class, 'destroy'])->name('sitepages.destroy');

    // Lead WhatsApp (statistik klik)
    Route::get('leads', [LeadController::class, 'index'])->name('leads.index');

    // Indexing & Peringkat Google
    Route::get('indexing', [IndexingController::class, 'index'])->name('indexing.index');
    Route::post('indexing/inspect', [IndexingController::class, 'inspect'])->name('indexing.inspect');
    Route::post('indexing/one', [IndexingController::class, 'inspectOne'])->name('indexing.one');
    Route::post('indexing/sync', [IndexingController::class, 'sync'])->name('indexing.sync');
    Route::post('indexing/requested', [IndexingController::class, 'markRequested'])->name('indexing.requested');
    Route::get('indexing/progress', [IndexingController::class, 'progress'])->name('indexing.progress');

    // Mulai Cepat (satu form -> semua terisi sampai hijau)
    Route::get('quickstart', [QuickStartController::class, 'index'])->name('quickstart.index');
    Route::post('quickstart', [QuickStartController::class, 'run'])->name('quickstart.run');
    Route::get('quickstart/status', [QuickStartController::class, 'status'])->name('quickstart.status');

    // Asisten SEO (diskusi + laporan + eksekusi aksi terbatas)
    Route::get('assistant', [AssistantController::class, 'index'])->name('assistant.index');
    Route::post('assistant/ask', [AssistantController::class, 'ask'])->name('assistant.ask');
    Route::get('assistant/report', [AssistantController::class, 'report'])->name('assistant.report');
    Route::post('assistant/execute', [AssistantController::class, 'execute'])->name('assistant.execute');

    // Pengaturan (WA, brand, schema)
    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

    // Import CSV + generate (Start/Pause/Resume/Status)
    Route::get('imports', [ImportController::class, 'index'])->name('imports.index');
    Route::post('imports', [ImportController::class, 'store'])->name('imports.store');
    Route::post('imports/{import}/pause', [ImportController::class, 'pause'])->name('imports.pause');
    Route::post('imports/{import}/resume', [ImportController::class, 'resume'])->name('imports.resume');
    Route::get('imports/{import}/status', [ImportController::class, 'status'])->name('imports.status');
    Route::delete('imports/{import}', [ImportController::class, 'destroy'])->name('imports.destroy');

    // Pages + publish queue
    Route::get('pages', [AdminPageController::class, 'index'])->name('pages.index');
    Route::post('pages/{page}/publish', [AdminPageController::class, 'publish'])->name('pages.publish');
    Route::post('pages/{page}/unpublish', [AdminPageController::class, 'unpublish'])->name('pages.unpublish');
    Route::post('publish-queue', [AdminPageController::class, 'publishQueue'])->name('publish.start');
    Route::get('publish-queue/status', [AdminPageController::class, 'publishStatus'])->name('publish.status');
    Route::post('publish-queue/pause', [AdminPageController::class, 'pausePublish'])->name('publish.pause');
    Route::post('publish-queue/resume', [AdminPageController::class, 'resumePublish'])->name('publish.resume');
});

/*
|--------------------------------------------------------------------------
| Catch-all pSEO (HARUS paling akhir)
| /{layanan}/{kota}/{kecamatan}/{kelurahan} dan halaman kategori 1–3 segmen
|--------------------------------------------------------------------------
*/
Route::get('/{path}', [PageController::class, 'show'])
    ->middleware([\App\Http\Middleware\ApplyThemeFingerprint::class, 'throttle:pseo'])
    ->where('path', '.*')
    ->name('pseo.page');
