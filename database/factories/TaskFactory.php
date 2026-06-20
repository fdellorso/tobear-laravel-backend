<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'completed' => false,
            'order' => fake()->numberBetween(0, 100),
            'user_id' => User::factory(),
        ];
    }
}
