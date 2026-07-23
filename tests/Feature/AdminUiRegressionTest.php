<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminUiRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        foreach ([
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/testing'),
            storage_path('framework/views'),
        ] as $path) {
            File::ensureDirectoryExists($path);
        }
    }

    public function test_login_page_uses_daya_ai_branding(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('DAYA AI', false);
        $response->assertDontSee('CEGU pSEO Engine', false);
    }

    public function test_admin_settings_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/settings');

        $response->assertOk();
        $response->assertSee('Pengaturan', false);
        $response->assertSee('Warna Situs', false);
    }

    public function test_dashboard_empty_leads_copy_no_longer_mentions_manual_migration(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertOk();
        $response->assertSee('Belum ada klik tercatat.', false);
        $response->assertDontSee('Butuh migrasi', false);
        $response->assertDontSee('php artisan migrate', false);
    }
}
