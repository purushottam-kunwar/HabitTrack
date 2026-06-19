<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MoodLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'      => User::factory(),
            'log_date'     => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'mood'         => fake()->numberBetween(1, 5),
            'energy_level' => fake()->numberBetween(1, 5),
            'notes'        => null,
        ];
    }

    public function today(): static
    {
        return $this->state(['log_date' => now()->toDateString()]);
    }
}
