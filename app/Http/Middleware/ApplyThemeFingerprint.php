<?php

namespace App\Http\Middleware;

use App\Support\ThemeFingerprint;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menerapkan sidik jari tema pada semua respons HTML PUBLIK: class `cegu-*`
 * diganti prefix unik instalasi. Dipasang hanya pada rute publik — panel
 * admin dan XML (sitemap) tidak disentuh.
 */
class ApplyThemeFingerprint
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $type = (string) $response->headers->get('Content-Type', '');
        if ($type === '' || str_contains($type, 'text/html')) {
            $content = $response->getContent();
            if (is_string($content) && $content !== '') {
                $response->setContent(ThemeFingerprint::apply($content));
            }
        }

        return $response;
    }
}
