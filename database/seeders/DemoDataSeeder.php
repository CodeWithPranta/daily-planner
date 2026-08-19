<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\DailyPlan;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use App\Services\DailyProductivityScoreService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed a demo user with categories, goals, and a day of planned tasks.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Pranta',
            'email' => 'pranta@example.com',
            'password' => bcrypt('password'),
        ]);

        $makeCat = function (string $name, string $icon, string $color, float $daily) use ($user): Category {
            return Category::factory()->for($user)->create([
                'name' => $name,
                'icon' => $icon,
                'color' => $color,
                'daily_target_hours' => $daily,
                'weekly_target_hours' => $daily * 6,
                'description' => __('Demo category'),
            ]);
        };

        $bcs = $makeCat('BCS Study', 'book-open', '#6366f1', 4.0);
        $coding = $makeCat('Coding', 'code-bracket', '#0ea5e9', 3.0);
        $spanish = $makeCat('Spanish', 'language', '#8b5cf6', 1.0);
        $health = $makeCat('Health & Exercise', 'heart', '#10b981', 0.75);
        $family = $makeCat('Family Time', 'home', '#f59e0b', 1.0);
        $freelancing = $makeCat('Freelancing', 'briefcase', '#ec4899', 3.0);

        Goal::factory()->for($user)->for($coding, 'category')->create([
            'title' => 'Become a Laravel Expert',
            'description' => 'Master Laravel by building real-world applications.',
            'target_date' => Carbon::now()->addMonths(16)->toDateString(),
            'target_value' => 10,
            'current_value' => 3,
            'status' => 'active',
        ]);

        $bcsGoal = Goal::factory()->for($user)->for($bcs, 'category')->create([
            'title' => 'Pass the BCS Preliminary Exam',
            'target_date' => Carbon::now()->addMonths(8)->toDateString(),
            'target_value' => 10,
            'current_value' => 4,
            'status' => 'active',
        ]);

        $plan = DailyPlan::factory()->for($user)->planned()->create(['date' => Carbon::today()->toDateString()]);

        // Today's tasks: a realistic mid-day mix (some done, one in progress,
        // the rest pending) so the dashboard/analytics show meaningful data.
        $tasks = [
            ['Study Bangladesh Affairs', $bcs, 120, 'high', $bcsGoal->id, 'completed'],
            ['Build Laravel Project', $coding, 180, 'high', null, 'in_progress'],
            ['Learn Spanish Vocabulary', $spanish, 60, 'medium', null, 'completed'],
            ['Exercise', $health, 45, 'medium', null, 'pending'],
            ['Read with kids', $family, 60, 'low', null, 'pending'],
            ['Send client invoices', $freelancing, 90, 'high', null, 'in_progress'],
        ];

        $scoreService = app(DailyProductivityScoreService::class);

        foreach ($tasks as [$title, $category, $minutes, $priority, $goalId, $status]) {
            $taskData = [
                'title' => $title,
                'planned_minutes' => $minutes,
                'priority' => $priority,
                'status' => $status,
                'goal_id' => $goalId,
            ];

            if ($status === 'completed') {
                $taskData['progress_percentage'] = 100;
                $taskData['actual_minutes'] = (int) ($minutes * fake()->randomFloat(2, 0.7, 1.0));
                $taskData['started_at'] = Carbon::today()->addHours(fake()->numberBetween(6, 9));
                $taskData['completed_at'] = Carbon::today()->addHours(fake()->numberBetween(10, 14));
            } elseif ($status === 'in_progress') {
                $progress = fake()->numberBetween(30, 70);
                $taskData['progress_percentage'] = $progress;
                $taskData['actual_minutes'] = (int) ($minutes * ($progress / 100));
                $taskData['started_at'] = Carbon::today()->addHours(fake()->numberBetween(8, 14));
            } else {
                $taskData['progress_percentage'] = 0;
                $taskData['actual_minutes'] = 0;
            }

            Task::factory()
                ->for($user)
                ->for($plan, 'dailyPlan')
                ->for($category, 'category')
                ->create($taskData);
        }

        // Store today's daily score so analytics/streaks reflect the current day
        // (not just historical days). This prevents the "This Week" range from
        // showing all zeros early in the week.
        $scoreService->storeForUser($user->id, Carbon::today());

        // Seed historical daily scores for the past 6 days (for streaks/reports)
        $histCats = [$bcs, $coding, $spanish, $health];
        for ($i = 6; $i >= 1; $i--) {
            $date = Carbon::today()->subDays($i);
            $histPlan = DailyPlan::factory()->for($user)->planned()->create(['date' => $date->toDateString()]);

            $histTasks = [
                ['Study Bangladesh Affairs', $bcs, 120, 'high'],
                ['Build Laravel Project', $coding, 180, 'high'],
                ['Learn Spanish Vocabulary', $spanish, 60, 'medium'],
            ];

            foreach ($histTasks as [$title, $category, $minutes, $priority]) {
                // ~80% completed, ~20% skipped
                $isCompleted = fake()->boolean(80);
                $isSkipped = ! $isCompleted && fake()->boolean(50);

                $taskData = [
                    'title' => $title,
                    'planned_minutes' => $minutes,
                    'priority' => $priority,
                ];

                if ($isCompleted) {
                    $taskData['status'] = 'completed';
                    $taskData['progress_percentage'] = 100;
                    $taskData['actual_minutes'] = (int) ($minutes * fake()->randomFloat(2, 0.6, 1.1));
                    $taskData['completed_at'] = $date->copy()->addHours(fake()->numberBetween(8, 20));
                    $taskData['started_at'] = $date->copy()->addHours(fake()->numberBetween(6, 8));
                } elseif ($isSkipped) {
                    $taskData['status'] = 'skipped';
                    $taskData['progress_percentage'] = 0;
                    $taskData['actual_minutes'] = 0;
                } else {
                    $progress = fake()->numberBetween(25, 75);
                    $taskData['status'] = 'in_progress';
                    $taskData['progress_percentage'] = $progress;
                    $taskData['actual_minutes'] = (int) ($minutes * ($progress / 100));
                    $taskData['started_at'] = $date->copy()->addHours(fake()->numberBetween(8, 14));
                }

                Task::factory()->for($user)->for($histPlan, 'dailyPlan')->for($category, 'category')->create($taskData);
            }

            $scoreService->storeForUser($user->id, $date);
        }

        $this->command->info('Demo user: pranta@example.com / password');
    }
}
