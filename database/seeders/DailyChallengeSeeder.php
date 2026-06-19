<?php

namespace Database\Seeders;

use App\Models\DailyChallenge;
use Illuminate\Database\Seeder;

class DailyChallengeSeeder extends Seeder
{
    public function run(): void
    {
        $challenges = [
            [
                'slug' => 'avoid-softdrinks',
                'name' => 'Avoid Soft Drinks',
                'description' => 'Don\'t drink any soft drinks today',
                'category' => 'health',
                'xp_reward' => 20,
            ],
            [
                'slug' => 'eat-fruits',
                'name' => 'Eat 2 Fruits',
                'description' => 'Log at least 2 fruit servings',
                'category' => 'health',
                'xp_reward' => 15,
                'daily_target' => 2,
                'unit' => 'servings',
            ],
            [
                'slug' => 'drink-water',
                'name' => 'Drink 8 Glasses of Water',
                'description' => 'Log 8 glasses (2L) of water',
                'category' => 'water',
                'xp_reward' => 25,
                'daily_target' => 8,
                'unit' => 'glasses',
            ],
            [
                'slug' => 'no-junk',
                'name' => 'No Junk Food',
                'description' => 'Avoid all junk food today',
                'category' => 'health',
                'xp_reward' => 30,
            ],
            [
                'slug' => 'budget-day',
                'name' => 'Stay Under ₹300',
                'description' => 'Spend less than ₹300 on food',
                'category' => 'budget',
                'xp_reward' => 20,
                'daily_target' => 300,
                'unit' => '₹',
            ],
        ];

        foreach ($challenges as $challenge) {
            DailyChallenge::firstOrCreate(
                ['slug' => $challenge['slug']],
                $challenge
            );
        }

        $this->command->info('Created/updated ' . count($challenges) . ' daily challenges.');
    }
}
