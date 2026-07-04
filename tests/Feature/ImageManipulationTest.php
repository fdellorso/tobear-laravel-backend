<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\ImageManipulation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageManipulationTest extends TestCase
{
    use RefreshDatabase;

    // --- index ---

    public function test_user_sees_only_own_manipulations()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        ImageManipulation::factory()->count(2)->create(['user_id' => $user1->id]);
        ImageManipulation::factory()->count(3)->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->getJson('/api/v1/image');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    // --- show ---

    public function test_user_can_show_own_manipulation()
    {
        $user = User::factory()->create();
        $manip = ImageManipulation::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson("/api/v1/image/{$manip->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $manip->id);
    }

    public function test_user_cannot_show_other_users_manipulation()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $manip = ImageManipulation::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->getJson("/api/v1/image/{$manip->id}");

        $response->assertStatus(403);
    }

    // --- destroy ---

    public function test_user_can_delete_own_manipulation()
    {
        $user = User::factory()->create();
        $manip = ImageManipulation::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson("/api/v1/image/{$manip->id}/delete");

        $response->assertStatus(204);

        $this->assertModelMissing($manip);
    }

    public function test_user_cannot_delete_other_users_manipulation()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $manip = ImageManipulation::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->postJson("/api/v1/image/{$manip->id}/delete");

        $response->assertStatus(403);

        $this->assertModelExists($manip);
    }

    // --- byAlbum ---

    public function test_user_can_list_manipulations_by_own_album()
    {
        $user = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user->id]);
        ImageManipulation::factory()->count(2)->create([
            'user_id' => $user->id,
            'album_id' => $album->id,
        ]);

        $response = $this->actingAs($user)->getJson("/api/v1/image/by-album/{$album->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_user_cannot_list_manipulations_by_other_users_album()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->getJson("/api/v1/image/by-album/{$album->id}");

        $response->assertStatus(403);
    }

    // --- resize ---

    public function test_authenticated_user_can_resize_an_image()
    {
        Storage::fake('public_uploads');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($user)->postJson('/api/v1/image/resize', [
            'image' => $file,
            'w' => 200,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('image_manipulations', [
            'type' => ImageManipulation::TYPE_RESIZE,
            'user_id' => $user->id,
        ]);
    }

    public function test_resize_validates_required_width()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($user)->postJson('/api/v1/image/resize', [
            'image' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['w']);
    }

    public function test_resize_with_other_users_album_returns_403()
    {
        Storage::fake('public_uploads');
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user2->id]);
        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($user1)->postJson('/api/v1/image/resize', [
            'image' => $file,
            'w' => 200,
            'album_id' => $album->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_resize_with_own_album_succeeds()
    {
        Storage::fake('public_uploads');
        $user = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user->id]);
        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($user)->postJson('/api/v1/image/resize', [
            'image' => $file,
            'w' => 200,
            'album_id' => $album->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('image_manipulations', [
            'type' => ImageManipulation::TYPE_RESIZE,
            'user_id' => $user->id,
            'album_id' => $album->id,
        ]);
    }
}
