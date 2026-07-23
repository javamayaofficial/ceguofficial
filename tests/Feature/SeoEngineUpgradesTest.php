<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Services\IndexNowService;
use App\Services\UniquenessService;
use Tests\TestCase;

/**
 * Tes ringan fitur "mesin terbaik" yang tidak butuh DB — hanya logika + config.
 */
class SeoEngineUpgradesTest extends TestCase
{
    public function test_indexnow_disabled_when_key_empty(): void
    {
        config()->set('services.indexnow.key', '');
        $this->assertFalse(IndexNowService::isEnabled());

        config()->set('services.indexnow.key', 'abc123');
        $this->assertTrue(IndexNowService::isEnabled());
    }

    public function test_uniqueness_counts_only_real_local_facts(): void
    {
        $svc = new UniquenessService();

        $page = new Page();
        $page->extra = [
            'harga' => '150000',
            'jumlah_tutor' => '12',
            'lat' => '-6.4',      // geo → tidak dihitung
            'lng' => '106.8',     // geo → tidak dihitung
            'kosong' => '',       // kosong → tidak dihitung
        ];

        $this->assertSame(2, $svc->localFactCount($page));
    }

    public function test_thin_detection_respects_threshold(): void
    {
        config()->set('daya.thin_min_local_facts', 2);
        $svc = new UniquenessService();

        $rich = new Page();
        $rich->extra = ['harga' => '150000', 'jumlah_tutor' => '12'];
        $this->assertFalse($svc->isThin($rich));

        $thin = new Page();
        $thin->extra = ['harga' => '150000'];
        $this->assertTrue($svc->isThin($thin));

        $empty = new Page();
        $empty->extra = [];
        $this->assertTrue($svc->isThin($empty));
    }
}
