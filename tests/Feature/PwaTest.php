<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_webmanifest_is_accessible(): void
    {
        $resp = $this->get(route('manifest'));

        $resp->assertOk();
        $resp->assertJsonPath('name', 'Daily Planner');
        $resp->assertJsonPath('short_name', 'Planner');
        $resp->assertJsonPath('display', 'standalone');
    }

    public function test_service_worker_is_accessible(): void
    {
        $resp = $this->get(route('sw'));

        $resp->assertOk();
    }

    public function test_offline_page_is_accessible(): void
    {
        $resp = $this->get(route('offline'));

        $resp->assertOk();
        $resp->assertSee('Offline');
    }

    public function test_icon_192_is_accessible(): void
    {
        $resp = $this->get('/icon-192.png');

        $resp->assertOk();
    }

    public function test_icon_512_is_accessible(): void
    {
        $resp = $this->get('/icon-512.png');

        $resp->assertOk();
    }

    public function test_app_shell_includes_manifest_link(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->get(route('dashboard'));

        $resp->assertOk();
        $resp->assertSee('manifest.webmanifest', false);
        $resp->assertSee('theme-color', false);
    }
}
