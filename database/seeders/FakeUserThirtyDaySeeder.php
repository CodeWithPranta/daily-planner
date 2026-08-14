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

class FakeUserThirtyDaySeeder extends Seeder
{
    /**
     * Seed a fake user with 30 days of historical task data and daily scores
     * that show a steady productivity score increase over time.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo30@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $study = Category::factory()->for($user)->create([
            'name' => 'Study',
            'icon' => 'book-open',
            'color' => '#6366f1',
            'daily_target_hours' => 4.0,
            'weekly_target_hours' => 24.0,
        ]);

        $coding = Category::factory()->for($user)->create([
            'name' => 'Coding',
            'icon' => 'code-bracket',
            'color' => '#0ea5e9',
            'daily_target_hours' => 3.0,
            'weekly_target_hours' => 18.0,
        ]);

        $health = Category::factory()->for($user)->create([
            'name' => 'Exercise',
            'icon' => 'heart',
            'color' => '#10b981',
            'daily_target_hours' => 1.0,
            'weekly_target_hours' => 6.0,
        ]);

        $language = Category::factory()->for($user)->create([
            'name' => 'Language',
            'icon' => 'language',
            'color' => '#8b5cf6',
            'daily_target_hours' => 1.0,
            'weekly_target_hours' => 6.0,
        ]);

        $goal = Goal::factory()->for($user)->for($coding, 'category')->create([
            'title' => 'Master Laravel',
            'description' => 'Become a Laravel expert by building real apps.',
            'target_date' => Carbon::now()->addMonths(6)->toDateString(),
            'target_value' => 10,
            'current_value' => 5,
            'status' => 'active',
        ]);

        $studyGoal = Goal::factory()->for($user)->for($study, 'category')->create([
            'title' => 'Read 10 Books',
            'target_date' => Carbon::now()->addMonths(4)->toDateString(),
            'target_value' => 10,
            'current_value' => 3,
            'status' => 'active',
        ]);

        $categories = [$study, $coding, $health, $language];
        $scoreService = app(DailyProductivityScoreService::class);

        $taskTemplates = [
            ['title' => 'Read textbook chapter', 'category' => $study, 'minutes' => 120, 'priority' => 'high'],
            ['title' => 'Build Laravel feature', 'category' => $coding, 'minutes' => 180, 'priority' => 'high'],
            ['title' => 'Learn new vocabulary', 'category' => $language, 'minutes' => 45, 'priority' => 'medium'],
            ['title' => 'Morning workout', 'category' => $health, 'minutes' => 45, 'priority' => 'medium'],
            ['title' => 'Write blog post', 'category' => $coding, 'minutes' => 60, 'priority' => 'low'],
        ];

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $completionRate = $this->completionRateForDay($i);

            $plan = DailyPlan::factory()->for($user)->planned()->create(['date' => $date->toDateString()]);

            foreach ($taskTemplates as $template) {
                $roll = fake()->randomFloat(2, 0, 1);
                $isCompleted = $roll < $completionRate;
                $isSkipped = ! $isCompleted && $roll < $completionRate + 0.1;

                $taskData = [
                    'title' => $template['title'],
                    'planned_minutes' => $template['minutes'],
                    'priority' => $template['priority'],
                ];

                if ($isCompleted) {
                    $taskData['status'] = 'completed';
                    $taskData['progress_percentage'] = 100;
                    $taskData['actual_minutes'] = (int) ($template['minutes'] * fake()->randomFloat(2, 0.75, 1.0));
                    $taskData['completed_at'] = $date->copy()->addHours(fake()->numberBetween(14, 19));
                    $taskData['started_at'] = $date->copy()->addHours(fake()->numberBetween(8, 13));
                } elseif ($isSkipped) {
                    $taskData['status'] = 'skipped';
                    $taskData['progress_percentage'] = 0;
                    $taskData['actual_minutes'] = 0;
                } else {
                    $progress = fake()->numberBetween(30, 80);
                    $taskData['status'] = 'in_progress';
                    $taskData['progress_percentage'] = $progress;
                    $taskData['actual_minutes'] = (int) ($template['minutes'] * ($progress / 100));
                    $taskData['started_at'] = $date->copy()->addHours(fake()->numberBetween(9, 15));
                }

                $goalId = ($template['category'] === $coding) ? $goal->id
                    : (($template['category'] === $study) ? $studyGoal->id : null);

                Task::factory()
                    ->for($user)
                    ->for($plan, 'dailyPlan')
                    ->for($template['category'], 'category')
                    ->create(array_merge($taskData, ['goal_id' => $goalId]));
            }

            $scoreService->storeForUser($user->id, $date);
        }

        $this->command->info('Fake user created: demo30@example.com / password');
        $this->command->info('30 days of data with increasing productivity scores.');
    }

    /**
     * Return a completion rate (0-1) that rises as the days get closer to today.
     * Day 29 (oldest) -> ~35%, day 0 (today) -> ~92%.
     */
    private function completionRateForDay(int $daysAgo): float
    {
        $progress = 1.0 - ($daysAgo / 29.0);

        return 0.35 + $progress * 0.57;
    }
}
