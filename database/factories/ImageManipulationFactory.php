<?php

namespace Database\Factories;

use App\Models\ImageManipulation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImageManipulationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->word().'.jpg',
            'path' => 'manipulations/'.fake()->uuid().'.jpg',
            'type' => ImageManipulation::TYPE_RESIZE,
            'data' => json_encode(['w' => 100, 'h' => 100]),
            'output_path' => 'manipulations/'.fake()->uuid().'.jpg',
            'user_id' => User::factory(),
            'album_id' => null,
        ];
    }
}
