<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            [
                'slug' => 'first-log',
                'name' => 'First Step',
                'description' => 'Log your first food item',
                'icon' => '📝',
                'xp_reward' => 5,
                'category' => 'logging',
            ],
            [
                'slug' => 'hundred-meals',
                'name' => 'Logged 100 Meals',
                'description' => 'Log 100 food items total',
                'icon' => '💯',
                'xp_reward' => 50,
                'category' => 'logging',
            ],
            [
                'slug' => '7-day-logging',
                'name' => '7-Day Logging Streak',
                'description' => 'Log food for 7 consecutive days',
                'icon' => '🔥',
                'xp_reward' => 25,
                'category' => 'streak',
            ],
            [
                'slug' => '14-day-logging',
                'name' => '2-Week Streak',
                'description' => 'Log food for 14 consecutive days',
                'icon' => '🌟',
                'xp_reward' => 60,
                'category' => 'streak',
            ],
            [
                'slug' => 'first-healthy-week',
                'name' => 'First Healthy Week',
                'description' => 'Complete a week with 70% or more healthy meals',
                'icon' => '🥗',
                'xp_reward' => 40,
                'category' => 'health',
            ],
            [
                'slug' => 'health-champion',
                'name' => 'Health Champion',
                'description' => 'Achieve 80% or more healthy meals in a week',
                'icon' => '🏆',
                'xp_reward' => 100,
                'category' => 'health',
            ],
            [
                'slug' => 'no-softdrinks-7',
                'name' => 'No Soft Drinks for 7 Days',
                'description' => 'Avoid soft drinks for a full week',
                'icon' => '🚫',
                'xp_reward' => 35,
                'category' => 'health',
            ],
            [
                'slug' => 'consistency-30',
                'name' => '30-Day Consistency',
                'description' => 'Log food on 25+ days in the last 30 days',
                'icon' => '📊',
                'xp_reward' => 75,
                'category' => 'logging',
            ],
            [
                'slug' => 'budget-master',
                'name' => 'Budget Master',
                'description' => 'Spend ₹100 or less per day for a week',
                'icon' => '💰',
                'xp_reward' => 30,
                'category' => 'budget',
            ],
            [
                'slug' => 'level-5',
                'name' => 'Level 5 Reached',
                'description' => 'Reach Level 5 (2500 XP)',
                'icon' => '⭐',
                'xp_reward' => 0,
                'category' => 'xp',
            ],
            [
                'slug' => 'level-10',
                'name' => 'Level 10 Reached',
                'description' => 'Reach Level 10 (5000 XP)',
                'icon' => '💎',
                'xp_reward' => 0,
                'category' => 'xp',
            ],
            [
                'slug' => '500-meals',
                'name' => 'Food Master',
                'description' => 'Log 500 food items total',
                'icon' => '👨‍🍳',
                'xp_reward' => 200,
                'category' => 'logging',
            ],
        ];

        foreach ($achievements as $achievement) {
            Achievement::firstOrCreate(
                ['slug' => $achievement['slug']],
                $achievement
            );
        }

        $this->command->info('Created/updated ' . count($achievements) . ' achievements.');
    }
}
