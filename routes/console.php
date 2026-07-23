<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Penjadwalan otomatis (butuh cron di server):
|   * * * * * cd /var/www/cegu && php artisan schedule:run >> /dev/null 2>&1
|--------------------------------------------------------------------------
| Semua tugas hanya berjalan bila fitur terkait dikonfigurasi (mengecek key),
| jadi aman diaktifkan sejak awal.
*/

// Kirim ulang URL terbaru ke IndexNow tiap hari (jaring pengaman bila ada yang
// terlewat saat publish). No-op bila INDEXNOW_KEY kosong.
Schedule::command('indexnow:submit --limit=10000')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->when(fn () => \App\Services\IndexNowService::isEnabled());

// Panaskan cache halaman terbaru tiap jam agar crawl selalu cepat.
Schedule::command('pages:warm --limit=1000')
    ->hourly()
    ->withoutOverlapping();

// Segarkan ringkasan Search Console (cache dashboard) tiap 6 jam.
Schedule::command('gsc:stats --days=28')
    ->everySixHours()
    ->withoutOverlapping()
    ->when(fn () => \App\Services\SearchConsole\SearchConsoleService::isConfigured());

