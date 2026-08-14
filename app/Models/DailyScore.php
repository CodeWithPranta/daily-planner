<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\DailyScoreFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property Carbon $date
 * @property float $score
 * @property int $total_tasks
 * @property int $completed_tasks
 * @property int $in_progress_tasks
 * @property int $pending_tasks
 * @property int $skipped_tasks
 * @property int $partial_tasks
 * @property int $total_planned_minutes
 * @property int $total_actual_minutes
 * @property string|null $category_breakdown
 * @property string|null $reflection
 */
class DailyScore extends Model
{
    /** @use HasFactory<DailyScoreFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'date', 'score',
        'total_tasks', 'completed_tasks', 'in_progress_tasks', 'pending_tasks', 'skipped_tasks', 'partial_tasks',
        'total_planned_minutes', 'total_actual_minutes',
        'category_breakdown', 'reflection',
    ];

    protected $casts = [
        'date' => 'date',
        'score' => 'float',
        'total_tasks' => 'integer',
        'completed_tasks' => 'integer',
        'in_progress_tasks' => 'integer',
        'pending_tasks' => 'integer',
        'skipped_tasks' => 'integer',
        'partial_tasks' => 'integer',
        'total_planned_minutes' => 'integer',
        'total_actual_minutes' => 'integer',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<DailyScore>  $query
     * @return Builder<DailyScore>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * @param  Builder<DailyScore>  $query
     * @return Builder<DailyScore>
     */
    public function scopeForDate(Builder $query, Carbon $date): Builder
    {
        return $query->whereDate('date', $date->toDateString());
    }

    /**
     * @param  Builder<DailyScore>  $query
     * @return Builder<DailyScore>
     */
    public function scopeBetweenDates(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('date', [
            $start->copy()->startOfDay()->format('Y-m-d H:i:s'),
            $end->copy()->endOfDay()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param  Builder<DailyScore>  $query
     * @return Builder<DailyScore>
     */
    public function scopeActiveDays(Builder $query): Builder
    {
        return $query->where('total_tasks', '>', 0);
    }

    /**
     * Whether this day counts toward a streak (at least one completed task).
     */
    public function countsAsStreakDay(): bool
    {
        return $this->completed_tasks > 0;
    }
}
