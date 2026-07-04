<?php

namespace Tests\Feature;

use App\Notifications\NewContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_send_contact_message()
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/contact', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Hello, this is a test message.',
        ]);

        $response->assertStatus(201);

        Notification::assertSentOnDemand(NewContactMessage::class);

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'john@example.com',
        ]);
    }

    public function test_contact_requires_email()
    {
        $response = $this->postJson('/api/v1/contact', [
            'name' => 'John Doe',
            'message' => 'Hello, this is a test message.',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_contact_requires_message()
    {
        $response = $this->postJson('/api/v1/contact', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_contact_name_is_optional()
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/contact', [
            'email' => 'john@example.com',
            'message' => 'Hello, this is a test message.',
        ]);

        $response->assertStatus(201);
    }

    public function test_contact_validates_email_format()
    {
        $response = $this->postJson('/api/v1/contact', [
            'email' => 'not-an-email',
            'message' => 'Hello, this is a test message.',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_also_send_contact_message()
    {
        Notification::fake();

        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/contact', [
            'email' => 'john@example.com',
            'message' => 'Hello from authenticated user.',
        ]);

        $response->assertStatus(201);
    }
}
