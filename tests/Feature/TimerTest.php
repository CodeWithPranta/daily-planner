<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Enums\TimerStatus;
use App\Models\Task;
use App\Models\TimerSession;
use App\Models\User;
use App\Services\TimerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimerTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_timer_creates_running_session_and_updates_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create(['status' => TaskStatus::Pending]);
        $service = app(TimerService::class);

        $session = $service->startForTask($task);

        $this->assertSame(TimerStatus::Running, $session->status);
        $this->assertSame($task->id, $session->task_id);
        $this->assertSame($user->id, $session->user_id);

        $task->refresh();
        $this->assertSame(TaskStatus::InProgress, $task->status);
        $this->assertNotNull($task->started_at);
    }

    public function test_pause_and_resume_timer_changes_status(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();
        $service = app(TimerService::class);

        $session = $service->startForTask($task);

        $paused = $service->pause($session);
        $this->assertSame(TimerStatus::Paused, $paused->status);
        $this->assertNotNull($paused->paused_at);

        $resumed = $service->resume($paused);
        $this->assertSame(TimerStatus::Running, $resumed->status);
        $this->assertNotNull($resumed->resumed_at);
        $this->assertNull($resumed->paused_at);
    }

    public function test_stop_timer_accumulates_total_seconds_and_logs_progress(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create([
            'actual_minutes' => 0,
            'progress_percentage' => 0,
            'status' => TaskStatus::Pending,
        ]);
        $service = app(TimerService::class);

        // Simulate a 2-minute session
        $session = TimerSession::factory()->for($user)->for($task, 'task')->create([
            'started_at' => Carbon::now()->subSeconds(120),
            'total_seconds' => 0,
            'status' => TimerStatus::Running,
        ]);

        $stopped = $service->stop($session, notes: 'Worked on coding', progressPercentage: 80);

        $this->assertSame(TimerStatus::Stopped, $stopped->status);
        $this->assertNotNull($stopped->ended_at);
        $this->assertGreaterThan(100, $stopped->total_seconds);

        $task->refresh();
        $this->assertSame(80, $task->progress_percentage);
        $this->assertSame(TaskStatus::InProgress, $task->status);
        $this->assertGreaterThan(0, $task->actual_minutes);
        $this->assertDatabaseHas('task_updates', ['task_id' => $task->id, 'notes' => 'Worked on coding']);
    }

    public function test_elapsed_seconds_calculated_from_timestamps(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();

        $session = TimerSession::factory()->for($user)->for($task, 'task')->create([
            'started_at' => Carbon::now()->subSeconds(3661), // 1h 1m 1s
            'total_seconds' => 0,
            'status' => TimerStatus::Running,
        ]);

        $elapsed = $session->elapsedSeconds();
        $this->assertGreaterThan(3600, $elapsed);
        $this->assertLessThan(3700, $elapsed);

        $human = $session->elapsedHuman();
        $this->assertStringStartsWith('01:01', $human);
    }

    public function test_starting_a_new_timer_stops_any_existing_active_timer(): void
    {
        $user = User::factory()->create();
        $task1 = Task::factory()->for($user)->create();
        $task2 = Task::factory()->for($user)->create();
        $service = app(TimerService::class);

        $session1 = $service->startForTask($task1);
        $this->assertSame(TimerStatus::Running, $session1->status);

        $session2 = $service->startForTask($task2);

        $session1->refresh();
        $this->assertSame(TimerStatus::Stopped, $session1->status);
        $this->assertSame(TimerStatus::Running, $session2->status);
    }

    public function test_get_active_timer_for_user_returns_running_or_paused(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();
        $service = app(TimerService::class);

        $this->assertNull($service->getActiveForUser($user->id));

        $session = $service->startForTask($task);
        $active = $service->getActiveForUser($user->id);

        $this->assertNotNull($active);
        $this->assertSame($session->id, $active->id);

        $service->stop($session);
        $this->assertNull($service->getActiveForUser($user->id));
    }
}
