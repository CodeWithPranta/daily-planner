<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DailyPlan;
use App\Models\Task;
use App\Models\User;
use App\Services\DailyPlanService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DailyPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_or_create_returns_the_same_plan_per_date(): void
    {
        $user = User::factory()->create();
        $date = Carbon::today();
        $service = app(DailyPlanService::class);

        $first = $service->getOrCreateFor($user, $date);
        $second = $service->getOrCreateFor($user, $date);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('daily_plans', 1);
    }

    public function test_duplicate_plans_for_same_user_date_are_rejected_by_unique_constraint(): void
    {
        $user = User::factory()->create();
        $date = Carbon::today()->toDateString();

        DailyPlan::factory()->for($user)->create(['date' => $date]);

        $this->expectException(QueryException::class);
        DailyPlan::factory()->for($user)->create(['date' => $date]);
    }

    public function test_visiting_today_creates_a_daily_plan(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test('today.index')->assertOk();

        $this->assertSame(
            1,
            DailyPlan::where('user_id', $user->id)->whereDate('date', Carbon::today())->count(),
        );
    }

    public function test_user_can_add_a_task_to_todays_plan(): void
    {
        $user = User::factory()->create();
        Category::factory()->for($user)->create(['name' => 'Coding']);
        $this->actingAs($user);

        Livewire::test('today.index')
            ->set('title', 'Build Laravel Project')
            ->set('planned_minutes', 180)
            ->set('priority', 'high')
            ->call('saveTask')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', ['user_id' => $user->id, 'title' => 'Build Laravel Project']);
    }

    public function test_duplicate_yesterday_copies_tasks_into_today(): void
    {
        $user = User::factory()->create();
        $yesterday = DailyPlan::factory()->for($user)->planned()->create(['date' => Carbon::yesterday()->toDateString()]);
        Task::factory()->for($user)->for($yesterday, 'dailyPlan')->create(['title' => 'Yesterday task']);

        $this->actingAs($user);

        Livewire::test('today.index')
            ->call('duplicateYesterday')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', ['user_id' => $user->id, 'title' => 'Yesterday task']);
        $todayTask = Task::where('title', 'Yesterday task')->where('user_id', $user->id)->latest('id')->first();
        $this->assertNotNull($todayTask?->dailyPlan?->date);
        $this->assertTrue($todayTask->dailyPlan->date->isToday());
    }

    public function test_user_cannot_edit_another_users_task(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $plan = DailyPlan::factory()->for($owner)->create(['date' => Carbon::today()->toDateString()]);
        $task = Task::factory()->for($owner)->for($plan, 'dailyPlan')->create();

        $this->actingAs($intruder);

        Livewire::test('today.index')->call('openEdit', $task->id)->assertForbidden();
        Livewire::test('today.index')->call('deleteTask', $task->id)->assertForbidden();
    }
}
