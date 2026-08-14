<?php

namespace Tests\Unit;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Category;
use App\Models\DailyPlan;
use App\Models\Task;
use App\Models\User;
use App\Services\DailyProductivityScoreService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyProductivityScoreTest extends TestCase
{
    use RefreshDatabase;

    private DailyProductivityScoreService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DailyProductivityScoreService::class);
    }

    public function test_empty_task_collection_returns_zero_score(): void
    {
        $result = $this->service->calculate(new Collection);

        $this->assertSame(0.0, $result->score);
        $this->assertSame(0, $result->totalCount);
        $this->assertSame(0, $result->completedCount);
    }

    public function test_all_completed_tasks_gives_high_score(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $plan = DailyPlan::factory()->for($user)->planned()->create(['date' => Carbon::today()]);

        Task::factory()->for($user)->for($plan, 'dailyPlan')->for($category, 'category')->create([
            'status' => TaskStatus::Completed,
            'progress_percentage' => 100,
            'planned_minutes' => 120,
            'actual_minutes' => 120,
            'priority' => TaskPriority::High,
        ]);

        $result = $this->service->calculateForUser($user->id, Carbon::today());

        $this->assertGreaterThan(85, $result->score);
        $this->assertSame(1, $result->completedCount);
        $this->assertSame(1, $result->totalCount);
    }

    public function test_all_pending_tasks_gives_zero_achievement_score(): void
    {
        $user = User::factory()->create();
        $plan = DailyPlan::factory()->for($user)->create(['date' => Carbon::today()]);

        Task::factory()->for($user)->for($plan, 'dailyPlan')->create([
            'status' => TaskStatus::Pending,
            'progress_percentage' => 0,
            'planned_minutes' => 120,
            'actual_minutes' => 0,
        ]);

        $result = $this->service->calculateForUser($user->id, Carbon::today());

        $this->assertLessThan(10, $result->score);
        $this->assertSame(1, $result->pendingCount);
    }

    public function test_high_priority_task_weighted_more_than_low(): void
    {
        $user = User::factory()->create();
        $plan = DailyPlan::factory()->for($user)->create(['date' => Carbon::today()]);

        // High priority completed, low priority pending — same planned time
        Task::factory()->for($user)->for($plan, 'dailyPlan')->create([
            'status' => TaskStatus::Completed,
            'progress_percentage' => 100,
            'planned_minutes' => 60,
            'actual_minutes' => 60,
            'priority' => TaskPriority::High,
        ]);
        Task::factory()->for($user)->for($plan, 'dailyPlan')->create([
            'status' => TaskStatus::Pending,
            'progress_percentage' => 0,
            'planned_minutes' => 60,
            'actual_minutes' => 0,
            'priority' => TaskPriority::Low,
        ]);

        $result = $this->service->calculateForUser($user->id, Carbon::today());

        // High weight (1.5×60=90) completed + Low weight (0.7×60=42) pending = 90/(90+42) ≈ 68% achievement
        $this->assertGreaterThan(60, $result->score);
        $this->assertLessThan(80, $result->score);
    }

    public function test_score_reflects_partial_progress(): void
    {
        $user = User::factory()->create();
        $plan = DailyPlan::factory()->for($user)->create(['date' => Carbon::today()]);

        Task::factory()->for($user)->for($plan, 'dailyPlan')->create([
            'status' => TaskStatus::PartiallyCompleted,
            'progress_percentage' => 50,
            'planned_minutes' => 120,
            'actual_minutes' => 60,
            'priority' => TaskPriority::Medium,
        ]);

        $result = $this->service->calculateForUser($user->id, Carbon::today());

        $this->assertGreaterThan(30, $result->score);
        $this->assertLessThan(70, $result->score);
        $this->assertSame(1, $result->partialCount);
    }

    public function test_category_breakdown_built_correctly(): void
    {
        $user = User::factory()->create();
        $coding = Category::factory()->for($user)->create(['name' => 'Coding']);
        $bcs = Category::factory()->for($user)->create(['name' => 'BCS Study']);
        $plan = DailyPlan::factory()->for($user)->create(['date' => Carbon::today()]);

        Task::factory()->for($user)->for($plan, 'dailyPlan')->for($coding, 'category')->create([
            'planned_minutes' => 120, 'actual_minutes' => 120, 'status' => TaskStatus::Completed, 'progress_percentage' => 100,
        ]);
        Task::factory()->for($user)->for($plan, 'dailyPlan')->for($bcs, 'category')->create([
            'planned_minutes' => 60, 'actual_minutes' => 30, 'status' => TaskStatus::PartiallyCompleted, 'progress_percentage' => 50,
        ]);

        $result = $this->service->calculateForUser($user->id, Carbon::today());

        $this->assertCount(2, $result->categoryBreakdown);
        // Sorted by actual desc — Coding (120) first
        $this->assertSame('Coding', $result->categoryBreakdown[0]['name']);
        $this->assertSame(100, $result->categoryBreakdown[0]['completion']);
        $this->assertSame('BCS Study', $result->categoryBreakdown[1]['name']);
        $this->assertSame(50, $result->categoryBreakdown[1]['completion']);
    }

    public function test_score_capped_at_100(): void
    {
        $user = User::factory()->create();
        $plan = DailyPlan::factory()->for($user)->create(['date' => Carbon::today()]);

        Task::factory()->for($user)->for($plan, 'dailyPlan')->create([
            'status' => TaskStatus::Completed,
            'progress_percentage' => 100,
            'planned_minutes' => 60,
            'actual_minutes' => 300, // Way more than planned
            'priority' => TaskPriority::High,
        ]);

        $result = $this->service->calculateForUser($user->id, Carbon::today());

        $this->assertLessThanOrEqual(100, $result->score);
        $this->assertSame(100, $result->scorePercent());
    }
}
