<?php

namespace Tests\Feature;

use App\Actions\CompleteTaskAction;
use App\Actions\UpdateTaskProgressAction;
use App\Enums\TaskStatus;
use App\Models\DailyPlan;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaskProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_task_detail(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();

        $this->get(route('tasks.show', $task))->assertRedirect(route('login'));
    }

    public function test_user_can_view_their_task_detail(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create(['title' => 'Study Laravel']);

        $this->actingAs($user);

        Livewire::test('tasks.show', ['task' => $task])
            ->assertOk()
            ->assertSee('Study Laravel');
    }

    public function test_user_cannot_view_another_users_task(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $task = Task::factory()->for($owner)->create();

        $this->actingAs($intruder);

        Livewire::test('tasks.show', ['task' => $task])->assertForbidden();
    }

    public function test_progress_update_action_creates_task_update_and_updates_task(): void
    {
        $user = User::factory()->create();
        $plan = DailyPlan::factory()->for($user)->create(['date' => Carbon::today()]);
        $task = Task::factory()->for($user)->for($plan, 'dailyPlan')->create([
            'status' => TaskStatus::Pending,
            'progress_percentage' => 0,
            'actual_minutes' => 0,
        ]);

        app(UpdateTaskProgressAction::class)->execute($task, [
            'progress_percentage' => 75,
            'worked_minutes' => 45,
            'notes' => 'Worked on chapter 3',
            'status' => TaskStatus::InProgress,
        ]);

        $task->refresh();
        $this->assertSame(75, $task->progress_percentage);
        $this->assertSame(45, $task->actual_minutes);
        $this->assertSame(TaskStatus::InProgress, $task->status);
        $this->assertDatabaseHas('task_updates', [
            'task_id' => $task->id,
            'progress_percentage' => 75,
            'worked_minutes' => 45,
            'notes' => 'Worked on chapter 3',
        ]);
    }

    public function test_complete_task_action_sets_status_and_creates_update(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create(['status' => TaskStatus::InProgress]);
        $action = app(CompleteTaskAction::class);

        $action->execute($task, notes: 'Done!', workedMinutes: 90);

        $task->refresh();
        $this->assertSame(TaskStatus::Completed, $task->status);
        $this->assertSame(100, $task->progress_percentage);
        $this->assertNotNull($task->completed_at);
        $this->assertSame(90, $task->actual_minutes);
        $this->assertDatabaseHas('task_updates', ['task_id' => $task->id, 'progress_percentage' => 100]);
    }

    public function test_livewire_component_can_log_progress(): void
    {
        $user = User::factory()->create();
        $plan = DailyPlan::factory()->for($user)->create(['date' => Carbon::today()]);
        $task = Task::factory()->for($user)->for($plan, 'dailyPlan')->create([
            'status' => TaskStatus::Pending,
            'actual_minutes' => 0,
            'progress_percentage' => 0,
        ]);

        $this->actingAs($user);

        Livewire::test('tasks.show', ['task' => $task])
            ->set('notes', 'Completed chapter 3')
            ->set('progress', 80)
            ->set('worked_hours', 1)
            ->set('worked_minutes', 30)
            ->set('status', 'in_progress')
            ->call('saveProgress')
            ->assertHasNoErrors();

        $task->refresh();
        $this->assertSame(80, $task->progress_percentage);
        $this->assertSame(90, $task->actual_minutes);
        $this->assertSame(TaskStatus::InProgress, $task->status);
    }

    public function test_multiple_progress_updates_accumulate_actual_time(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create(['actual_minutes' => 0]);
        $action = app(UpdateTaskProgressAction::class);

        $action->execute($task, ['progress_percentage' => 30, 'worked_minutes' => 45, 'status' => TaskStatus::InProgress]);
        $action->execute($task->refresh(), ['progress_percentage' => 75, 'worked_minutes' => 60, 'status' => TaskStatus::InProgress]);
        $action->execute($task->refresh(), ['progress_percentage' => 100, 'worked_minutes' => 30, 'status' => TaskStatus::Completed]);

        $task->refresh();
        $this->assertSame(135, $task->actual_minutes);
        $this->assertSame(100, $task->progress_percentage);
        $this->assertSame(3, $task->updates()->count());
    }
}
