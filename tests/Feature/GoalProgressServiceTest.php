<?php

namespace Tests\Feature;

use App\Actions\CompleteTaskAction;
use App\Enums\GoalStatus;
use App\Enums\TaskStatus;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use App\Services\GoalProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_increments_current_value_from_completed_tasks(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->for($user)->create([
            'target_value' => 10,
            'current_value' => 0,
            'status' => GoalStatus::Active,
        ]);

        Task::factory()->for($user)->create(['goal_id' => $goal->id, 'status' => TaskStatus::Completed]);
        Task::factory()->for($user)->create(['goal_id' => $goal->id, 'status' => TaskStatus::Completed]);
        Task::factory()->for($user)->create(['goal_id' => $goal->id, 'status' => TaskStatus::Pending]);

        $service = app(GoalProgressService::class);
        $service->syncFromTasks($goal);

        $goal->refresh();
        $this->assertEquals(2.0, (float) $goal->current_value);
        $this->assertSame(GoalStatus::Active, $goal->status);
    }

    public function test_sync_auto_completes_goal_when_target_reached(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->for($user)->create([
            'target_value' => 3,
            'current_value' => 0,
            'status' => GoalStatus::Active,
        ]);

        Task::factory()->for($user)->create(['goal_id' => $goal->id, 'status' => TaskStatus::Completed]);
        Task::factory()->for($user)->create(['goal_id' => $goal->id, 'status' => TaskStatus::Completed]);
        Task::factory()->for($user)->create(['goal_id' => $goal->id, 'status' => TaskStatus::Completed]);

        $service = app(GoalProgressService::class);
        $service->syncFromTasks($goal);

        $goal->refresh();
        $this->assertEquals(3.0, (float) $goal->current_value);
        $this->assertSame(GoalStatus::Completed, $goal->status);
    }

    public function test_sync_ignores_goal_without_target_value(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->for($user)->create([
            'target_value' => null,
            'current_value' => null,
            'status' => GoalStatus::Active,
        ]);

        Task::factory()->for($user)->create(['goal_id' => $goal->id, 'status' => TaskStatus::Completed]);

        $service = app(GoalProgressService::class);
        $service->syncFromTasks($goal);

        $goal->refresh();
        $this->assertNull($goal->current_value);
    }

    public function test_sync_for_user_updates_all_goals_with_targets(): void
    {
        $user = User::factory()->create();
        $goal1 = Goal::factory()->for($user)->create(['target_value' => 5, 'current_value' => 0]);
        $goal2 = Goal::factory()->for($user)->create(['target_value' => 10, 'current_value' => 0]);

        Task::factory()->for($user)->create(['goal_id' => $goal1->id, 'status' => TaskStatus::Completed]);
        Task::factory()->for($user)->create(['goal_id' => $goal2->id, 'status' => TaskStatus::Completed]);
        Task::factory()->for($user)->create(['goal_id' => $goal2->id, 'status' => TaskStatus::Completed]);

        $service = app(GoalProgressService::class);
        $count = $service->syncForUser($user->id);

        $this->assertSame(2, $count);
        $this->assertEquals(1.0, (float) $goal1->refresh()->current_value);
        $this->assertEquals(2.0, (float) $goal2->refresh()->current_value);
    }

    public function test_time_based_goal_uses_actual_minutes(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->for($user)->create([
            'target_value' => 500,
            'current_value' => 0,
            'status' => GoalStatus::Active,
        ]);

        Task::factory()->for($user)->create([
            'goal_id' => $goal->id,
            'status' => TaskStatus::Completed,
            'actual_minutes' => 120,
        ]);
        Task::factory()->for($user)->create([
            'goal_id' => $goal->id,
            'status' => TaskStatus::Completed,
            'actual_minutes' => 180,
        ]);

        $service = app(GoalProgressService::class);
        $service->syncFromTasks($goal);

        $goal->refresh();
        $this->assertEquals(300.0, (float) $goal->current_value);
    }

    public function test_complete_task_action_syncs_goal_progress(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->for($user)->create([
            'target_value' => 2,
            'current_value' => 0,
            'status' => GoalStatus::Active,
        ]);

        $task1 = Task::factory()->for($user)->create(['goal_id' => $goal->id, 'status' => TaskStatus::Pending]);
        $task2 = Task::factory()->for($user)->create(['goal_id' => $goal->id, 'status' => TaskStatus::Pending]);

        $action = app(CompleteTaskAction::class);
        $action->execute($task1);

        $goal->refresh();
        $this->assertEquals(1.0, (float) $goal->current_value);

        $action->execute($task2);

        $goal->refresh();
        $this->assertEquals(2.0, (float) $goal->current_value);
        $this->assertSame(GoalStatus::Completed, $goal->status);
    }
}
