<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FoodItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'     => fake()->word(),
            'category' => fake()->randomElement(['healthy', 'unhealthy']),
            'type'     => fake()->randomElement(['homemade', 'junk', 'readymade', 'drink']),
            'calories' => fake()->numberBetween(50, 800),
            'unit'     => fake()->randomElement(['serving', 'piece', 'bowl', 'glass']),
        ];
    }

    public function healthy(): static
    {
        return $this->state(['category' => 'healthy', 'type' => 'homemade', 'calories' => fake()->numberBetween(50, 300)]);
    }

    public function unhealthy(): static
    {
        return $this->state(['category' => 'unhealthy', 'type' => 'readymade', 'calories' => fake()->numberBetween(300, 800)]);
    }

    public function junk(): static
    {
        return $this->state(['category' => 'unhealthy', 'type' => 'junk', 'calories' => fake()->numberBetween(400, 900)]);
    }
}
