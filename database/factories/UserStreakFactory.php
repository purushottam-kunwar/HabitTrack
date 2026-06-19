<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserStreakFactory extends Factory
{
    public function definition(): array
    {
        $streak = fake()->numberBetween(0, 30);
        return [
            'user_id'             => User::factory(),
            'logging_streak'      => $streak,
            'logging_streak_best' => $streak,
            'healthy_streak'      => fake()->numberBetween(0, $streak),
            'healthy_streak_best' => $streak,
            'consistency_score'   => fake()->numberBetween(0, 100),
            'last_logged_date'    => now()->toDateString(),
        ];
    }
}
