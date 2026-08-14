<?php

namespace App\Models;

use App\Enums\TimerStatus;
use Carbon\Carbon;
use Database\Factories\TimerSessionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $task_id
 * @property Carbon $started_at
 * @property Carbon|null $paused_at
 * @property Carbon|null $resumed_at
 * @property Carbon|null $ended_at
 * @property int $total_seconds
 * @property TimerStatus $status
 */
class TimerSession extends Model
{
    /** @use HasFactory<TimerSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'task_id', 'started_at', 'paused_at', 'resumed_at',
        'ended_at', 'total_seconds', 'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'paused_at' => 'datetime',
        'resumed_at' => 'datetime',
        'ended_at' => 'datetime',
        'total_seconds' => 'integer',
        'status' => TimerStatus::class,
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * @param  Builder<TimerSession>  $query
     * @return Builder<TimerSession>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [TimerStatus::Running, TimerStatus::Paused]);
    }

    /**
     * @param  Builder<TimerSession>  $query
     * @return Builder<TimerSession>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Calculate elapsed seconds from timestamps, accounting for pauses.
     */
    public function elapsedSeconds(): int
    {
        if ($this->status === TimerStatus::Stopped) {
            return $this->total_seconds;
        }

        $now = Carbon::now();

        if ($this->status === TimerStatus::Paused) {
            $end = $this->paused_at ?? $now;
        } else {
            $end = $this->resumed_at ?? $now;
        }

        $total = $this->total_seconds;
        $segmentStart = $this->resumed_at ?? $this->started_at;
        $total += (int) $segmentStart->diffInSeconds($end);

        return max(0, $total);
    }

    /**
     * Human-readable elapsed time (HH:MM:SS).
     */
    public function elapsedHuman(): string
    {
        $seconds = $this->elapsedSeconds();
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
}
