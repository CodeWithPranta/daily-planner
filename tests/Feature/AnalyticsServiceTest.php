<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DailyPlan;
use App\Models\DailyScore;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\CategoryInsightService;
use Carbon\Carbon;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_with_no_data_returns_zeros(): void
    {
        $user = User::factory()->create();
        $service = app(AnalyticsService::class);

        $data = $service->getAnalytics($user->id, 'week');

        $this->assertSame(0.0, $data->averageScore);
        $this->assertSame(0, $data->totalCompleted);
        $this->assertSame(0, $data->totalTasks);
        $this->assertSame(0, $data->currentStreak);
        $this->assertEmpty($data->categoryDistribution);
    }

    public function test_analytics_aggregates_score_trend(): void
    {
        $user = User::factory()->create();
        $today = Carbon::today();

        DailyScore::factory()->for($user)->create([
            'date' => $today->toDateString(),
            'score' => 80,
            'completed_tasks' => 4,
            'total_tasks' => 5,
        ]);
        DailyScore::factory()->for($user)->create([
            'date' => $today->copy()->subDay()->toDateString(),
            'score' => 60,
            'completed_tasks' => 3,
            'total_tasks' => 5,
        ]);

        // Debug: verify scores are stored
        $directCount = DailyScore::forUser($user->id)->count();
        $this->assertSame(2, $directCount, 'Scores should be stored');

        $service = app(AnalyticsService::class);
        $data = $service->getAnalytics($user->id, 'week');

        // Build expected: the week starts Monday (Aug 10). Today is Thursday (Aug 14) = index 4, yesterday is Wednesday (Aug 13) = index 3
        $todayIndex = (int) $today->copy()->startOfWeek(Carbon::MONDAY)->diffInDays($today);
        $yesterdayIndex = $todayIndex - 1;

        $this->assertSame(7, count($data->scoreTrend));
        $this->assertEquals(80.0, $data->scoreTrend[$todayIndex]['score']);
        $this->assertEquals(60.0, $data->scoreTrend[$yesterdayIndex]['score']);
        $this->assertEquals(70.0, $data->averageScore);
        $this->assertSame(7, $data->totalCompleted);
        $this->assertSame(10, $data->totalTasks);
    }

    public function test_analytics_returns_active_goals(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create(['name' => 'Coding', 'color' => '#0ea5e9']);

        Goal::factory()->for($user)->for($category, 'category')->create([
            'title' => 'Learn Laravel',
            'target_value' => 10,
            'current_value' => 3,
            'status' => 'active',
        ]);

        $service = app(AnalyticsService::class);
        $data = $service->getAnalytics($user->id, 'week');

        $this->assertCount(1, $data->goals);
        $this->assertSame('Learn Laravel', $data->goals[0]['title']);
        $this->assertSame(30, $data->goals[0]['progress']);
    }

    public function test_category_insight_time_distribution(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create(['name' => 'Study', 'color' => '#6366f1']);
        $plan = DailyPlan::factory()->for($user)->create(['date' => Carbon::today()]);
        Task::factory()->for($user)->for($plan, 'dailyPlan')->for($category, 'category')->create([
            'actual_minutes' => 120,
            'status' => 'completed',
        ]);
        Task::factory()->for($user)->for($plan, 'dailyPlan')->create([
            'actual_minutes' => 60,
            'status' => 'completed',
        ]);

        $service = app(CategoryInsightService::class);
        $dist = $service->timeDistribution($user->id, Carbon::today()->subDay(), Carbon::today());

        $this->assertCount(2, $dist);
        $this->assertSame('Study', $dist[0]['label']);
        $this->assertSame(120, $dist[0]['value']);
    }

    public function test_analytics_page_loads_for_authed_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->get(route('analytics'));
        $resp->assertOk();
    }

    public function test_analytics_page_with_seeded_data_loads(): void
    {
        $this->seed(DemoDataSeeder::class);
        $user = User::where('email', 'pranta@example.com')->first();
        $this->actingAs($user);

        $resp = $this->get(route('analytics'));
        $resp->assertOk();
    }

    public function test_analytics_range_switch_works(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test('analytics.index')
            ->set('range', 'month')
            ->assertHasNoErrors();
    }
}
