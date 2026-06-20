<?php

namespace Tests\Feature;

use App\Models\Image;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_upload_an_image()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($user)->postJson('/api/v1/images', [
            'image' => $file,
            'label' => 'Foto profilo',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('images', [
            'label' => 'Foto profilo',
            'user_id' => $user->id,
        ]);
    }

    public function test_store_requires_image_file()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/images', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_store_validates_image_mime_type()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($user)->postJson('/api/v1/images', [
            'image' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_user_sees_only_own_images()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Image::factory()->count(2)->create(['user_id' => $user1->id]);
        Image::factory()->count(3)->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->getJson('/api/v1/images');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_delete_own_image()
    {
        $user = User::factory()->create();
        $image = Image::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->deleteJson("/api/v1/images/{$image->id}");

        $response->assertStatus(204);

        $this->assertModelMissing($image);
    }

    public function test_user_cannot_delete_other_users_image()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $image = Image::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->deleteJson("/api/v1/images/{$image->id}");

        $response->assertStatus(403);

        $this->assertModelExists($image);
    }
}
