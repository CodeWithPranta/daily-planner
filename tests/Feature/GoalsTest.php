<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GoalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_goal(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test('goals.index')
            ->set('title', 'Become a Laravel Expert')
            ->set('target_value', '100')
            ->set('current_value', '42')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('goals', ['user_id' => $user->id, 'title' => 'Become a Laravel Expert']);
    }

    public function test_goal_progress_is_calculated_from_target_value(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->for($user)->create([
            'target_value' => 100,
            'current_value' => 42,
            'status' => 'active',
        ]);

        $this->assertSame(42, $goal->progress_percentage);
    }

    public function test_goal_without_target_shows_completed_as_100(): void
    {
        $goal = Goal::factory()->for(User::factory())->create(['target_value' => null, 'current_value' => null, 'status' => 'completed']);

        $this->assertSame(100, $goal->progress_percentage);
    }

    public function test_user_cannot_access_another_users_goal(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->for($owner)->create();

        $this->actingAs($intruder);

        Livewire::test('goals.index')->call('edit', $goal->id)->assertForbidden();
        Livewire::test('goals.index')->call('delete', $goal->id)->assertForbidden();
    }
}
