<?php

namespace App\Models;

use App\Concerns\HasColorBadge;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Carbon\Carbon;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $daily_plan_id
 * @property int|null $category_id
 * @property int|null $goal_id
 * @property string $title
 * @property string|null $description
 * @property int $planned_minutes
 * @property int $actual_minutes
 * @property int $progress_percentage
 * @property TaskPriority $priority
 * @property TaskStatus $status
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 */
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasColorBadge, HasFactory;

    protected $fillable = [
        'user_id', 'daily_plan_id', 'category_id', 'goal_id', 'title', 'description',
        'planned_minutes', 'actual_minutes', 'progress_percentage',
        'priority', 'status', 'started_at', 'completed_at', 'order_column',
    ];

    protected $casts = [
        'planned_minutes' => 'integer',
        'actual_minutes' => 'integer',
        'progress_percentage' => 'integer',
        'priority' => TaskPriority::class,
        'status' => TaskStatus::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function color(): string
    {
        return $this->status->color();
    }

    public function priorityBadge(): string
    {
        return self::badgeClasses($this->priority->color());
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<DailyPlan, $this>
     */
    public function dailyPlan(): BelongsTo
    {
        return $this->belongsTo(DailyPlan::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Goal, $this>
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /**
     * @return HasMany<TaskUpdate, $this>
     */
    public function updates(): HasMany
    {
        return $this->hasMany(TaskUpdate::class)->orderByDesc('created_at');
    }

    /**
     * @return HasMany<TimerSession, $this>
     */
    public function timerSessions(): HasMany
    {
        return $this->hasMany(TimerSession::class);
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeForDate(Builder $query, Carbon $date): Builder
    {
        return $query->whereHas('dailyPlan', fn ($q) => $q->whereDate('date', $date->toDateString()));
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', TaskStatus::Pending);
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', TaskStatus::Completed);
    }
}
