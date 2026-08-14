<?php

namespace Tests\Feature;

use App\Enums\DailyPlanStatus;
use App\Enums\TaskStatus;
use App\Models\DailyPlan;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_review(): void
    {
        $this->get(route('review.daily'))->assertRedirect(route('login'));
    }

    public function test_authed_user_can_view_review_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->get(route('review.daily'));

        $resp->assertOk();
    }

    public function test_review_page_shows_completed_and_pending_tasks(): void
    {
        $user = User::factory()->create();
        $plan = DailyPlan::factory()->for($user)->planned()->create(['date' => Carbon::today()]);

        $completedTask = Task::factory()->for($user)->for($plan, 'dailyPlan')->create([
            'title' => 'Completed Task',
            'status' => TaskStatus::Completed,
            'progress_percentage' => 100,
        ]);
        $pendingTask = Task::factory()->for($user)->for($plan, 'dailyPlan')->create([
            'title' => 'Pending Task',
            'status' => TaskStatus::Pending,
        ]);

        $this->actingAs($user);

        Livewire::test('review.daily')
            ->assertOk()
            ->assertSee('Completed Task')
            ->assertSee('Pending Task');
    }

    public function test_submit_review_saves_reflection_and_daily_score(): void
    {
        $user = User::factory()->create();
        $plan = DailyPlan::factory()->for($user)->planned()->create(['date' => Carbon::today()]);
        Task::factory()->for($user)->for($plan, 'dailyPlan')->create(['status' => TaskStatus::Completed, 'progress_percentage' => 100]);

        $this->actingAs($user);

        Livewire::test('review.daily')
            ->set('reflection', 'Great productive day!')
            ->call('submitReview')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('daily_scores', [
            'user_id' => $user->id,
            'date' => Carbon::today()->startOfDay()->format('Y-m-d H:i:s'),
            'reflection' => 'Great productive day!',
        ]);

        $this->assertSame(DailyPlanStatus::Reviewed, $plan->refresh()->status);
    }

    public function test_complete_day_sets_plan_status_to_completed(): void
    {
        $user = User::factory()->create();
        $plan = DailyPlan::factory()->for($user)->planned()->create(['date' => Carbon::today()]);
        Task::factory()->for($user)->for($plan, 'dailyPlan')->create(['status' => TaskStatus::Completed, 'progress_percentage' => 100]);

        $this->actingAs($user);

        Livewire::test('review.daily')
            ->call('completeDay')
            ->assertHasNoErrors();

        $this->assertSame(DailyPlanStatus::Completed, $plan->refresh()->status);
        $this->assertDatabaseHas('daily_scores', [
            'user_id' => $user->id,
            'date' => Carbon::today()->startOfDay()->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_review_shows_no_tasks_message_when_day_is_empty(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test('review.daily')
            ->assertOk()
            ->assertSee(__('No tasks were planned for this day.'));
    }

    public function test_review_with_date_offset_shows_previous_day(): void
    {
        $user = User::factory()->create();
        $yesterday = Carbon::yesterday();
        $plan = DailyPlan::factory()->for($user)->planned()->create(['date' => $yesterday->toDateString()]);
        Task::factory()->for($user)->for($plan, 'dailyPlan')->create([
            'title' => 'Yesterday Task',
            'status' => TaskStatus::Completed,
            'progress_percentage' => 100,
        ]);

        $this->actingAs($user);

        Livewire::test('review.daily', ['dateOffset' => 1])
            ->assertOk()
            ->assertSee('Yesterday Task');
    }
}
