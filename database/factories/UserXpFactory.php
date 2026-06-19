<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserXpFactory extends Factory
{
    public function definition(): array
    {
        $totalXp = fake()->numberBetween(0, 5000);
        return [
            'user_id'       => User::factory(),
            'total_xp'      => $totalXp,
            'current_level' => intval($totalXp / 500) + 1,
            'xp_in_level'   => $totalXp % 500,
        ];
    }

    public function withXp(int $xp): static
    {
        return $this->state([
            'total_xp'      => $xp,
            'current_level' => intval($xp / 500) + 1,
            'xp_in_level'   => $xp % 500,
        ]);
    }
}
