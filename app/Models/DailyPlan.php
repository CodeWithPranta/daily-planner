<?php

namespace App\Models;

use App\Enums\DailyPlanStatus;
use Carbon\Carbon;
use Database\Factories\DailyPlanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property Carbon $date
 * @property DailyPlanStatus $status
 */
class DailyPlan extends Model
{
    /** @use HasFactory<DailyPlanFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'date', 'status'];

    protected $casts = [
        'date' => 'date',
        'status' => DailyPlanStatus::class,
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('order_column')->orderBy('id');
    }

    /**
     * @param  Builder<DailyPlan>  $query
     * @return Builder<DailyPlan>
     */
    public function scopeForDate(Builder $query, Carbon $date): Builder
    {
        return $query->whereDate('date', $date->toDateString());
    }

    /**
     * @param  Builder<DailyPlan>  $query
     * @return Builder<DailyPlan>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
