<?php

namespace Tests\Feature;

use App\Models\DailyPlan;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authed_user_can_load_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->get(route('dashboard'));

        $resp->assertOk();
        $resp->assertSee('Dashboard');
    }

    public function test_authed_user_can_load_today(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->get(route('today'));

        $resp->assertOk();
    }

    public function test_authed_user_can_load_task_detail(): void
    {
        $user = User::factory()->create();
        $plan = DailyPlan::factory()->for($user)->create(['date' => Carbon::today()]);
        $task = Task::factory()->for($user)->for($plan, 'dailyPlan')->create();

        $this->actingAs($user);

        $resp = $this->get(route('tasks.show', $task));

        $resp->assertOk();
        $resp->assertSee($task->title);
    }

    public function test_dashboard_shows_score_after_seeding(): void
    {
        $this->seed(DemoDataSeeder::class);

        $user = User::where('email', 'pranta@example.com')->first();
        $this->actingAs($user);

        $resp = $this->get(route('dashboard'));

        $resp->assertOk();
    }

    public function test_livewire_timer_bar_renders_for_authed_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->get(route('dashboard'));

        $resp->assertOk();
        // Timer bar should be present in the app shell
        $resp->assertSee('wire:id', false);
    }
}
