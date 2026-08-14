<?php

namespace Tests\Feature;

use App\Models\DailyScore;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_weekly_summary_with_no_data_returns_zeros(): void
    {
        $user = User::factory()->create();
        $service = app(ReportService::class);

        $summary = $service->weeklySummary($user->id);

        $this->assertSame(0, $summary->totalCompleted);
        $this->assertSame(0, $summary->totalTasks);
        $this->assertSame(0.0, $summary->averageScore);
        $this->assertSame(0, $summary->activeDayCount);
        $this->assertSame(0, $summary->completionPercent());
    }

    public function test_weekly_summary_aggregates_scores_correctly(): void
    {
        $user = User::factory()->create();
        $today = Carbon::today();

        for ($i = 0; $i < 3; $i++) {
            DailyScore::factory()->for($user)->create([
                'date' => $today->copy()->subDays($i)->toDateString(),
                'score' => 60 + $i * 10,
                'completed_tasks' => 3,
                'total_tasks' => 5,
                'total_planned_minutes' => 300,
                'total_actual_minutes' => 180,
            ]);
        }

        $service = app(ReportService::class);
        $summary = $service->weeklySummary($user->id);

        $this->assertSame(9, $summary->totalCompleted);
        $this->assertSame(15, $summary->totalTasks);
        $this->assertSame(900, $summary->totalPlannedMinutes);
        $this->assertSame(540, $summary->totalActualMinutes);
        $this->assertSame(3, $summary->activeDayCount);
        $this->assertSame(60, $summary->completionPercent());
        $this->assertSame(3, $summary->streakDayCount);
    }

    public function test_weekly_summary_average_score(): void
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

        $service = app(ReportService::class);
        $summary = $service->weeklySummary($user->id);

        $this->assertSame(70.0, $summary->averageScore);
    }

    public function test_monthly_summary_with_data(): void
    {
        $user = User::factory()->create();
        $today = Carbon::today();

        for ($i = 0; $i < 5; $i++) {
            DailyScore::factory()->for($user)->create([
                'date' => $today->copy()->subDays($i)->toDateString(),
                'score' => 50 + $i * 10,
                'completed_tasks' => 2 + $i,
                'total_tasks' => 5,
                'total_planned_minutes' => 300,
                'total_actual_minutes' => 200,
            ]);
        }

        $service = app(ReportService::class);
        $summary = $service->monthlySummary($user->id);

        $this->assertSame(5, $summary->activeDayCount);
        $this->assertSame(20, $summary->totalCompleted); // 2+3+4+5+6
        $this->assertSame(25, $summary->totalTasks);
        $this->assertSame(1500, $summary->totalPlannedMinutes);

        // Best day should be the most recent (score 90)
        $this->assertNotNull($summary->bestDay);
        $this->assertSame(90.0, (float) $summary->bestDay->score);
    }

    public function test_monthly_summary_consistency_percent(): void
    {
        $user = User::factory()->create();
        $today = Carbon::today();
        $daysInMonth = $today->daysInMonth;

        // Create scores for 10 days
        for ($i = 0; $i < 10; $i++) {
            DailyScore::factory()->for($user)->create([
                'date' => $today->copy()->subDays($i)->toDateString(),
                'score' => 70,
                'completed_tasks' => 3,
                'total_tasks' => 5,
            ]);
        }

        $service = app(ReportService::class);
        $summary = $service->monthlySummary($user->id);

        $expected = (int) round(10 / $daysInMonth * 100);
        $this->assertSame($expected, $summary->consistencyPercent());
    }

    public function test_trend_data_returns_array_of_score_points(): void
    {
        $user = User::factory()->create();
        $today = Carbon::today();

        DailyScore::factory()->for($user)->create(['date' => $today->toDateString(), 'score' => 75.5]);
        DailyScore::factory()->for($user)->create(['date' => $today->copy()->subDay()->toDateString(), 'score' => 60.0]);

        $service = app(ReportService::class);
        $summary = $service->weeklySummary($user->id);
        $trend = $service->trendData($summary->scores);

        $this->assertCount(2, $trend);
        $this->assertArrayHasKey('date', $trend[0]);
        $this->assertArrayHasKey('score', $trend[0]);
        $this->assertArrayHasKey('completed', $trend[0]);
    }
}
