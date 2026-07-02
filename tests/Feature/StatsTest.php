<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_stats()
    {
        $user = User::factory()->create();
        Task::factory()->count(3)->create(['user_id' => $user->id, 'completed' => false]);
        Task::factory()->count(2)->create(['user_id' => $user->id, 'completed' => true]);

        $response = $this->actingAs($user)->getJson('/api/v1/stats');

        $response->assertStatus(200)
            ->assertJsonPath('total', 5)
            ->assertJsonPath('completed', 2)
            ->assertJsonPath('active', 3)
            ->assertJsonPath('completion_rate', 40);
    }

    public function test_stats_returns_zero_for_user_with_no_tasks()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/stats');

        $response->assertStatus(200)
            ->assertJsonPath('total', 0)
            ->assertJsonPath('completed', 0)
            ->assertJsonPath('active', 0)
            ->assertJsonPath('completion_rate', 0);
    }

    public function test_stats_only_counts_own_tasks()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Task::factory()->count(4)->create(['user_id' => $user1->id]);
        Task::factory()->count(10)->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->getJson('/api/v1/stats');

        $response->assertStatus(200)
            ->assertJsonPath('total', 4);
    }

    public function test_unauthenticated_user_cannot_get_stats()
    {
        $response = $this->getJson('/api/v1/stats');
        $response->assertStatus(401);
    }
}
