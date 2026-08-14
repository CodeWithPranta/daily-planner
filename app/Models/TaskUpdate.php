<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Carbon\Carbon;
use Database\Factories\TaskUpdateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $task_id
 * @property int $progress_percentage
 * @property int $worked_minutes
 * @property string|null $notes
 * @property TaskStatus $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class TaskUpdate extends Model
{
    /** @use HasFactory<TaskUpdateFactory> */
    use HasFactory;

    protected $fillable = [
        'task_id', 'progress_percentage', 'worked_minutes', 'notes', 'status',
    ];

    protected $casts = [
        'progress_percentage' => 'integer',
        'worked_minutes' => 'integer',
        'status' => TaskStatus::class,
    ];

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
