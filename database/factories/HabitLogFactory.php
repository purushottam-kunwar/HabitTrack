<?php

namespace Database\Factories;

use App\Models\FoodItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class HabitLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'      => User::factory(),
            'food_item_id' => FoodItem::factory(),
            'log_date'     => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'quantity'     => fake()->numberBetween(1, 3),
            'amount_spent' => fake()->randomFloat(2, 10, 200),
            'notes'        => null,
        ];
    }

    public function today(): static
    {
        return $this->state(['log_date' => now()->toDateString()]);
    }

    public function forUser(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }
}
