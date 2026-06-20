<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    // --- store ---

    public function test_authenticated_user_can_create_a_task()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/tasks', [
            'title' => 'Comprare il latte',
            'description' => 'Andare al supermercato',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Comprare il latte')
            ->assertJsonPath('data.description', 'Andare al supermercato');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Comprare il latte',
            'user_id' => $user->id,
        ]);
    }

    public function test_store_validates_required_fields()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/tasks', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    // --- index ---

    public function test_user_sees_only_own_tasks()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Task::factory()->count(2)->create(['user_id' => $user1->id]);
        Task::factory()->count(3)->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->getJson('/api/v1/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    // --- show ---

    public function test_user_can_show_own_task()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $task->id);
    }

    public function test_user_cannot_show_other_users_task()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->getJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    // --- update ---

    public function test_user_can_update_own_task()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id, 'title' => 'Vecchio titolo']);

        $response = $this->actingAs($user)->putJson("/api/v1/tasks/{$task->id}", [
            'title' => 'Nuovo titolo',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Nuovo titolo');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Nuovo titolo',
        ]);
    }

    public function test_user_cannot_update_other_users_task()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->putJson("/api/v1/tasks/{$task->id}", [
            'title' => 'Hackerato',
        ]);

        $response->assertStatus(403);
    }

    public function test_update_validates_title_max_length()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->putJson("/api/v1/tasks/{$task->id}", [
            'title' => str_repeat('a', 256),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_update_validates_description_max_length()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->putJson("/api/v1/tasks/{$task->id}", [
            'description' => str_repeat('a', 1001),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }

    // --- destroy ---

    public function test_user_can_delete_own_task()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Task eliminato.');

        $this->assertModelMissing($task);
    }

    public function test_user_cannot_delete_other_users_task()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(403);

        $this->assertModelExists($task);
    }

    // --- reorder ---

    public function test_user_can_reorder_own_tasks()
    {
        $user = User::factory()->create();
        $task1 = Task::factory()->create(['user_id' => $user->id, 'order' => 0]);
        $task2 = Task::factory()->create(['user_id' => $user->id, 'order' => 1]);
        $task3 = Task::factory()->create(['user_id' => $user->id, 'order' => 2]);

        $response = $this->actingAs($user)->patchJson('/api/v1/tasks/reorder', [
            'tasks' => [$task3->id, $task2->id, $task1->id],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Ordine aggiornato con successo.');

        $this->assertDatabaseHas('tasks', ['id' => $task3->id, 'order' => 0]);
        $this->assertDatabaseHas('tasks', ['id' => $task2->id, 'order' => 1]);
        $this->assertDatabaseHas('tasks', ['id' => $task1->id, 'order' => 2]);
    }

    public function test_user_cannot_reorder_other_users_tasks()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->patchJson('/api/v1/tasks/reorder', [
            'tasks' => [$task->id],
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', "Alcune task non appartengono all\u{2019}utente.");
    }

    public function test_reorder_validates_required_tasks_array()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson('/api/v1/tasks/reorder', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tasks']);
    }
}
