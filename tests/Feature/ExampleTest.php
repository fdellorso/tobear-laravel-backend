<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_application_returns_a_successful_response(): void
    {
        if (! file_exists(public_path('app/index.html'))) {
            $this->markTestSkipped('Frontend build not found: public/app/index.html missing.');
        }

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
