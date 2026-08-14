<?php

namespace App\Services;

use App\Models\DailyScore;
use Carbon\Carbon;

/**
 * Calculates streaks based on consecutive days with at least one completed task.
 */
class StreakService
{
    /**
     * Get the user's current streak (consecutive days ending today or yesterday).
     */
    public function currentStreak(int $userId): int
    {
        $today = Carbon::today();

        // Check if today has a qualifying score; if not, start from yesterday
        $todayScore = DailyScore::forUser($userId)->forDate($today)->first();
        $streak = 0;

        if ($todayScore && $todayScore->countsAsStreakDay()) {
            $streak = 1;
        }

        // Walk backward from yesterday
        $date = $today->copy()->subDay();
        while ($this->hasStreakDay($userId, $date)) {
            $streak++;
            $date->subDay();
        }

        return $streak;
    }

    /**
     * Get the user's longest streak ever.
     */
    public function longestStreak(int $userId): int
    {
        $scores = DailyScore::forUser($userId)
            ->activeDays()
            ->orderBy('date')
            ->get();

        if ($scores->isEmpty()) {
            return 0;
        }

        $longest = 0;
        $current = 0;
        $prevDate = null;

        foreach ($scores as $score) {
            if (! $score->countsAsStreakDay()) {
                $current = 0;
                $prevDate = $score->date;

                continue;
            }

            if ($prevDate === null) {
                $current = 1;
            } else {
                $diff = (int) $prevDate->diffInDays($score->date);
                if ($diff === 1) {
                    $current++;
                } else {
                    $current = 1;
                }
            }

            $longest = max($longest, $current);
            $prevDate = $score->date;
        }

        return $longest;
    }

    /**
     * Get the user's streak history for the last N days (for display).
     *
     * @return array<int, array{date: Carbon, has_activity: bool, completed_tasks: int, score: float}>
     */
    public function recentStreakHistory(int $userId, int $days = 30): array
    {
        $end = Carbon::today();
        $start = $end->copy()->subDays($days - 1);

        $scores = DailyScore::forUser($userId)
            ->betweenDates($start, $end)
            ->orderBy('date')
            ->get()
            ->keyBy(fn (DailyScore $s) => $s->date->toDateString());

        $history = [];
        $date = $start->copy();
        while ($date <= $end) {
            $score = $scores->get($date->toDateString());

            $history[] = [
                'date' => $date->copy(),
                'has_activity' => $score !== null && $score->countsAsStreakDay(),
                'completed_tasks' => $score !== null ? $score->completed_tasks : 0,
                'score' => $score !== null ? (float) $score->score : 0.0,
            ];
            $date->addDay();
        }

        return $history;
    }

    /**
     * Check if a given date counts as a streak day for the user.
     */
    private function hasStreakDay(int $userId, Carbon $date): bool
    {
        $score = DailyScore::forUser($userId)->forDate($date)->first();

        return $score !== null && $score->countsAsStreakDay();
    }
}
