<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Percayai proxy edge agar Laravel mengenali skema HTTPS asli.
        $middleware->trustProxies(
            at: '*',
            headers:
                Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO
        );

        // Beacon pelacakan lead bersifat stateless (dipanggil dari halaman
        // publik yang mungkin ter-cache) → dikecualikan dari verifikasi CSRF.
        $middleware->validateCsrfTokens(except: [
            'track/wa',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
