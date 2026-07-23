<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Halaman statis situs (Tentang Kami, Layanan, Kontak, dsb).
 *
 * Slug dilindungi: tidak boleh bentrok dengan rute sistem maupun dengan slug
 * layanan pSEO, karena semuanya berbagi ruang URL satu segmen.
 */
class SitePage extends Model
{
    protected $fillable = [
        'slug', 'title', 'menu_label', 'meta_description', 'hero_image',
        'image_1', 'image_1_alt', 'image_2', 'image_2_alt',
        'image_3', 'image_3_alt', 'image_4', 'image_4_alt',
        'content', 'sort_order', 'show_in_nav', 'show_in_footer', 'is_active',
    ];

    protected $casts = [
        'show_in_nav' => 'boolean',
        'show_in_footer' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** Slug yang dipakai sistem — tidak boleh dipakai halaman statis. */
    public const RESERVED = [
        'admin', 'login', 'logout', 'register', 'sitemap', 'robots',
        'indexnow', 'track', 'storage', 'api', 'p',
    ];

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function url(): string
    {
        return url('/' . $this->slug);
    }

    public function label(): string
    {
        return $this->menu_label ?: $this->title;
    }

    /**
     * Gambar isi yang terisi, lengkap dengan alt.
     * Alt kosong otomatis diisi dari judul halaman + urutan agar tetap
     * deskriptif dan tidak sama persis antar gambar.
     *
     * @return array<int,array{url:string,alt:string}>
     */
    public function images(): array
    {
        $out = [];
        for ($i = 1; $i <= 4; $i++) {
            $url = trim((string) $this->{"image_{$i}"});
            if ($url === '') {
                continue;
            }
            $alt = trim((string) $this->{"image_{$i}_alt"});
            $out[] = [
                'url' => $url,
                'alt' => $alt !== '' ? $alt : trim($this->title . ' - gambar ' . (count($out) + 1)),
            ];
        }

        return $out;
    }
}
