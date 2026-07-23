<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Client AI dipilih dari config/services.php ('ai.driver').
        // Semua kode cukup bergantung pada kontrak AiChatClient.
        $this->app->bind(
            \App\Services\Ai\AiChatClient::class,
            fn () => \App\Services\Ai\AiClientFactory::make(),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Pagination ringan tanpa framework CSS (panel admin tidak pakai Tailwind/Bootstrap).
        Paginator::defaultView('vendor.pagination.cegu');
        Paginator::defaultSimpleView('vendor.pagination.cegu');

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // Rate limit halaman publik (per IP) untuk meredam bot/crawler agresif.
        RateLimiter::for('pseo', function (Request $request) {
            $max = (int) config('daya.public_rate_limit', 120);

            return [Limit::perMinute($max)->by($request->ip())];
        });
    }
}
