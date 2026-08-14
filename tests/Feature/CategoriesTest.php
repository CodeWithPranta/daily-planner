<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_categories_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('categories.index'))->assertOk();
    }

    public function test_guests_cannot_view_categories(): void
    {
        $this->get(route('categories.index'))->assertRedirect(route('login'));
    }

    public function test_user_can_create_a_category(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test('categories.index')
            ->set('name', 'BCS Study')
            ->set('color', '#6366f1')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', ['user_id' => $user->id, 'name' => 'BCS Study']);
    }

    public function test_category_names_are_unique_per_user(): void
    {
        $user = User::factory()->create();
        Category::factory()->for($user)->create(['name' => 'Coding']);

        $this->actingAs($user);

        Livewire::test('categories.index')
            ->set('name', 'Coding')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    public function test_two_users_can_share_a_category_name(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        Category::factory()->for($a)->create(['name' => 'Coding']);
        Category::factory()->for($b)->create(['name' => 'Coding']);

        $this->assertCount(2, Category::where('name', 'Coding')->get());
    }

    public function test_a_user_cannot_edit_another_users_category(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $category = Category::factory()->for($owner)->create();

        $this->actingAs($intruder);

        Livewire::test('categories.index')
            ->call('edit', $category->id)
            ->assertForbidden();
    }

    public function test_archiving_a_category_with_tasks_archives_instead_of_deleting(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        Task::factory()->for($user)->for($category, 'category')->create();

        $this->actingAs($user);

        Livewire::test('categories.index')
            ->call('confirmArchive', $category->id)
            ->assertSet('showArchiveConfirm', true)
            ->call('archive');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'is_active' => false]);
    }
}
