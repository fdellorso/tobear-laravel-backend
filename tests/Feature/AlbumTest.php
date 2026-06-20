<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlbumTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_an_album()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/albums', [
            'name' => 'Viaggio a Roma',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Viaggio a Roma');

        $this->assertDatabaseHas('albums', [
            'name' => 'Viaggio a Roma',
            'user_id' => $user->id,
        ]);
    }

    public function test_store_requires_name()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/albums', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_user_sees_only_own_albums()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Album::factory()->count(2)->create(['user_id' => $user1->id]);
        Album::factory()->count(3)->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->getJson('/api/v1/albums');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_show_own_album()
    {
        $user = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson("/api/v1/albums/{$album->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $album->id);
    }

    public function test_user_cannot_show_other_users_album()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->getJson("/api/v1/albums/{$album->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_update_own_album()
    {
        $user = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user->id, 'name' => 'Vecchio nome']);

        $response = $this->actingAs($user)->putJson("/api/v1/albums/{$album->id}", [
            'name' => 'Nuovo nome',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Nuovo nome');

        $this->assertDatabaseHas('albums', [
            'id' => $album->id,
            'name' => 'Nuovo nome',
        ]);
    }

    public function test_user_cannot_update_other_users_album()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->putJson("/api/v1/albums/{$album->id}", [
            'name' => 'Hackerato',
        ]);

        $response->assertStatus(403);
    }

    public function test_update_requires_name()
    {
        $user = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->putJson("/api/v1/albums/{$album->id}", [
            'name' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_user_can_delete_own_album()
    {
        $user = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->deleteJson("/api/v1/albums/{$album->id}");

        $response->assertStatus(204);

        $this->assertModelMissing($album);
    }

    public function test_user_cannot_delete_other_users_album()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->deleteJson("/api/v1/albums/{$album->id}");

        $response->assertStatus(403);

        $this->assertModelExists($album);
    }
}
