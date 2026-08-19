<?php

namespace Tests\Feature;

use App\Actions\CompleteTaskAction;
use App\Enums\TaskStatus;
use App\Models\DailyPlan;
use App\Models\DailyScore;
use App\Models\Task;
use App\Models\User;
use App\Services\DailyProductivityScoreService;
use App\Services\StreakService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StreakServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_scores_returns_zero_streak(): void
    {
        $user = User::factory()->create();
        $service = app(StreakService::class);

        $this->assertSame(0, $service->currentStreak($user->id));
        $this->assertSame(0, $service->longestStreak($user->id));
    }

    public function test_current_streak_counts_consecutive_days_with_completed_tasks(): void
    {
        $user = User::factory()->create();
        $today = Carbon::today();

        // 3 consecutive days ending today
        for ($i = 0; $i < 3; $i++) {
            DailyScore::factory()->for($user)->create([
                'date' => $today->copy()->subDays($i)->toDateString(),
                'completed_tasks' => 2,
                'total_tasks' => 5,
            ]);
        }

        $service = app(StreakService::class);

        $this->assertSame(3, $service->currentStreak($user->id));
    }

    public function test_current_streak_starts_from_yesterday_if_today_empty(): void
    {
        $user = User::factory()->create();
        $yesterday = Carbon::yesterday();

        DailyScore::factory()->for($user)->create([
            'date' => $yesterday->toDateString(),
            'completed_tasks' => 1,
            'total_tasks' => 3,
        ]);

        $service = app(StreakService::class);

        $this->assertSame(1, $service->currentStreak($user->id));
    }

    public function test_streak_breaks_on_gap_day(): void
    {
        $user = User::factory()->create();
        $today = Carbon::today();

        // Today + yesterday: streak
        for ($i = 0; $i < 2; $i++) {
            DailyScore::factory()->for($user)->create([
                'date' => $today->copy()->subDays($i)->toDateString(),
                'completed_tasks' => 1,
                'total_tasks' => 3,
            ]);
        }

        // 3 days ago: gap
        DailyScore::factory()->for($user)->create([
            'date' => $today->copy()->subDays(3)->toDateString(),
            'completed_tasks' => 2,
            'total_tasks' => 4,
        ]);

        $service = app(StreakService::class);

        $this->assertSame(2, $service->currentStreak($user->id));
    }

    public function test_day_with_zero_completed_does_not_break_streak_but_excludes_from_count(): void
    {
        $user = User::factory()->create();
        $today = Carbon::today();

        // Today: active
        DailyScore::factory()->for($user)->create([
            'date' => $today->toDateString(),
            'completed_tasks' => 3,
            'total_tasks' => 5,
        ]);

        // Yesterday: tasks but none completed
        DailyScore::factory()->for($user)->create([
            'date' => $today->copy()->subDay()->toDateString(),
            'completed_tasks' => 0,
            'total_tasks' => 4,
        ]);

        $service = app(StreakService::class);

        $this->assertSame(1, $service->currentStreak($user->id));
    }

    public function test_longest_streak_finds_max_run(): void
    {
        $user = User::factory()->create();
        $base = Carbon::today()->subDays(20);

        // Old streak of 5 days
        for ($i = 0; $i < 5; $i++) {
            DailyScore::factory()->for($user)->create([
                'date' => $base->copy()->addDays($i)->toDateString(),
                'completed_tasks' => 2,
                'total_tasks' => 4,
                'score' => 60.0,
            ]);
        }

        // Gap then current 3-day streak
        for ($i = 0; $i < 3; $i++) {
            DailyScore::factory()->for($user)->create([
                'date' => Carbon::today()->subDays($i)->toDateString(),
                'completed_tasks' => 1,
                'total_tasks' => 3,
                'score' => 70.0,
            ]);
        }

        $service = app(StreakService::class);

        $this->assertSame(5, $service->longestStreak($user->id));
    }

    public function test_recent_streak_history_fills_gaps(): void
    {
        $user = User::factory()->create();
        $today = Carbon::today();

        DailyScore::factory()->for($user)->create([
            'date' => $today->toDateString(),
            'completed_tasks' => 2,
            'total_tasks' => 5,
            'score' => 65.0,
        ]);

        $service = app(StreakService::class);
        $history = $service->recentStreakHistory($user->id, 7);

        $this->assertCount(7, $history);
        $this->assertTrue($history[6]['has_activity']);
        $this->assertFalse($history[0]['has_activity']);
        $this->assertSame(2, $history[6]['completed_tasks']);
        $this->assertSame(65.0, $history[6]['score']);
    }

    public function test_completing_a_task_updates_streak_without_running_review(): void
    {
        $user = User::factory()->create();
        $plan = DailyPlan::factory()->for($user)->create(['date' => Carbon::today()]);
        $task = Task::factory()
            ->for($user)
            ->for($plan, 'dailyPlan')
            ->create(['status' => TaskStatus::Pending]);

        app(CompleteTaskAction::class)->execute($task);

        $service = app(StreakService::class);
        $this->assertSame(1, $service->currentStreak($user->id));

        // The cached daily score is refreshed so analytics reads real data.
        $score = DailyScore::forUser($user->id)->forDate(Carbon::today())->first();
        $this->assertNotNull($score);
        $this->assertSame(1, $score->completed_tasks);
        $this->assertSame(1, $score->total_tasks);
    }

    public function test_streak_counts_consecutive_days_of_completed_tasks(): void
    {
        $user = User::factory()->create();
        $today = Carbon::today();

        foreach ([$today, $today->copy()->subDay()] as $date) {
            $plan = DailyPlan::factory()->for($user)->create(['date' => $date]);
            $task = Task::factory()
                ->for($user)
                ->for($plan, 'dailyPlan')
                ->create(['status' => TaskStatus::Pending]);

            app(CompleteTaskAction::class)->execute($task);
        }

        $this->assertSame(2, app(StreakService::class)->currentStreak($user->id));
    }

    public function test_refreshing_score_after_task_completion_preserves_review_reflection(): void
    {
        $user = User::factory()->create();
        $plan = DailyPlan::factory()->for($user)->create(['date' => Carbon::today()]);
        $task = Task::factory()
            ->for($user)
            ->for($plan, 'dailyPlan')
            ->create(['status' => TaskStatus::Pending]);

        // Simulate a prior end-of-day review that left a reflection.
        app(DailyProductivityScoreService::class)->storeForUser(
            $user->id,
            Carbon::today(),
            'Great focus today.',
        );

        app(CompleteTaskAction::class)->execute($task);

        $score = DailyScore::forUser($user->id)->forDate(Carbon::today())->first();
        $this->assertNotNull($score);
        $this->assertSame('Great focus today.', $score->reflection);
        $this->assertSame(1, $score->completed_tasks);
    }
}
