<?php

namespace App\Http\Controllers;

use App\Services\SitemapService;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(private readonly SitemapService $sitemap)
    {
    }

    public function index(): Response
    {
        return response($this->sitemap->indexXml(), 200, ['Content-Type' => 'application/xml']);
    }

    public function chunk(int $n): Response
    {
        if ($n < 1 || $n > $this->sitemap->chunkCount()) {
            abort(404);
        }

        return response($this->sitemap->chunkXml($n), 200, ['Content-Type' => 'application/xml']);
    }

    /** Sitemap beranda + halaman statis. */
    public function static(SitemapService $sitemap)
    {
        return response($sitemap->staticXml(), 200, ['Content-Type' => 'application/xml']);
    }
}
