<?php

namespace App\Models;

use App\Concerns\HasColorBadge;
use App\Enums\GoalStatus;
use Carbon\Carbon;
use Database\Factories\GoalFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $category_id
 * @property string $title
 * @property string|null $description
 * @property Carbon|null $target_date
 * @property string|null $target_value
 * @property string|null $current_value
 * @property GoalStatus $status
 * @property int $progress_percentage
 */
class Goal extends Model
{
    /** @use HasFactory<GoalFactory> */
    use HasColorBadge, HasFactory;

    protected $fillable = [
        'user_id', 'category_id', 'title', 'description',
        'target_date', 'target_value', 'current_value', 'status',
    ];

    protected $casts = [
        'target_date' => 'date',
        'target_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'status' => GoalStatus::class,
    ];

    public function color(): string
    {
        return 'violet';
    }

    /**
     * @return Attribute<int, never>
     */
    protected function progressPercentage(): Attribute
    {
        return new Attribute(
            get: function (?string $value, array $attributes): int {
                $target = $attributes['target_value'] ?? null;
                if ($target === null || (float) $target <= 0) {
                    return ($attributes['status'] ?? null) === GoalStatus::Completed->value ? 100 : 0;
                }
                $cur = (float) ($attributes['current_value'] ?? 0);

                return (int) min(100, max(0, round($cur / (float) $target * 100)));
            },
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @param  Builder<Goal>  $query
     * @return Builder<Goal>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', GoalStatus::Active);
    }

    /**
     * @param  Builder<Goal>  $query
     * @return Builder<Goal>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
