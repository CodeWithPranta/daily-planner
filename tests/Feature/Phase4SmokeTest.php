<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_page_loads_for_authed_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->get(route('review.daily'));
        $resp->assertOk();
    }

    public function test_weekly_report_loads_for_authed_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->get(route('reports.weekly'));
        $resp->assertOk();
    }

    public function test_monthly_report_loads_for_authed_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->get(route('reports.monthly'));
        $resp->assertOk();
    }

    public function test_review_page_with_seeded_data_loads(): void
    {
        $this->seed(DemoDataSeeder::class);
        $user = User::where('email', 'pranta@example.com')->first();
        $this->actingAs($user);

        $resp = $this->get(route('review.daily'));
        $resp->assertOk();
    }

    public function test_weekly_report_with_seeded_data_loads(): void
    {
        $this->seed(DemoDataSeeder::class);
        $user = User::where('email', 'pranta@example.com')->first();
        $this->actingAs($user);

        $resp = $this->get(route('reports.weekly'));
        $resp->assertOk();
    }

    public function test_monthly_report_with_seeded_data_loads(): void
    {
        $this->seed(DemoDataSeeder::class);
        $user = User::where('email', 'pranta@example.com')->first();
        $this->actingAs($user);

        $resp = $this->get(route('reports.monthly'));
        $resp->assertOk();
    }

    public function test_weekly_report_with_offset_loads(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->get(route('reports.weekly.offset', ['weekOffset' => 1]));
        $resp->assertOk();
    }

    public function test_monthly_report_with_offset_loads(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->get(route('reports.monthly.offset', ['monthOffset' => 1]));
        $resp->assertOk();
    }

    public function test_dashboard_end_my_day_links_to_review(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->get(route('dashboard'));
        $resp->assertOk();
        $resp->assertSee(route('review.daily'));
    }
}
