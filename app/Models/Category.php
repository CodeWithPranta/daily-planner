<?php

namespace App\Models;

use App\Concerns\HasColorBadge;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property string $icon
 * @property string|null $color
 * @property string|null $weekly_target_hours
 * @property string|null $daily_target_hours
 * @property bool $is_active
 * @property int|null $daily_target_minutes
 */
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasColorBadge, HasFactory;

    protected $fillable = [
        'user_id', 'name', 'description', 'icon', 'color',
        'weekly_target_hours', 'daily_target_hours', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'weekly_target_hours' => 'decimal:2',
        'daily_target_hours' => 'decimal:2',
    ];

    public function color(): string
    {
        return 'brand';
    }

    /**
     * @return Attribute<int|null, never>
     */
    protected function dailyTargetMinutes(): Attribute
    {
        return new Attribute(
            get: fn (?string $value, array $attributes): ?int => $attributes['daily_target_hours'] !== null
                ? (int) round((float) $attributes['daily_target_hours'] * 60)
                : null,
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
     * @return HasMany<Goal, $this>
     */
    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @param  Builder<Category>  $query
     * @return Builder<Category>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Category>  $query
     * @return Builder<Category>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
