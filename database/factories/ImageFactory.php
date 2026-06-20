<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'path' => 'images/'.fake()->uuid().'.jpg',
            'label' => fake()->word(),
            'user_id' => User::factory(),
        ];
    }
}
