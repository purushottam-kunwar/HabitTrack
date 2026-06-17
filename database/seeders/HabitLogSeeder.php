<?php

namespace Database\Seeders;

use App\Models\FoodItem;
use App\Models\HabitLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class HabitLogSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::findOrFail(1);

        $healthyIds   = FoodItem::where('category', 'healthy')->pluck('id')->toArray();
        $unhealthyIds = FoodItem::where('category', 'unhealthy')->pluck('id')->toArray();

        // 7 weeks of data with distinct personalities
        // healthRatio = fraction of entries that are healthy (0.0–1.0)
        $weeks = [
            // oldest → newest
            ['label' => 'Mixed start',           'healthRatio' => 0.55, 'daysOff' => [6],       'logsPerDay' => [3, 5]],
            ['label' => 'Cheat week',            'healthRatio' => 0.15, 'daysOff' => [],         'logsPerDay' => [5, 8]],
            ['label' => 'Detox week',            'healthRatio' => 0.90, 'daysOff' => [0],        'logsPerDay' => [4, 6]],
            ['label' => 'Balanced',              'healthRatio' => 0.60, 'daysOff' => [5, 6],     'logsPerDay' => [3, 5]],
            ['label' => 'Festival week',         'healthRatio' => 0.20, 'daysOff' => [],         'logsPerDay' => [6, 9]],
            ['label' => 'Back on track',         'healthRatio' => 0.85, 'daysOff' => [6],        'logsPerDay' => [3, 5]],
            ['label' => 'This week (current)',   'healthRatio' => 0.65, 'daysOff' => [],         'logsPerDay' => [3, 6]],
        ];

        // Realistic spend buckets (Rs) per category
        $healthySpend   = [0, 0, 0, 20, 30, 40, 50, 60, 80, 100, 120];
        $unhealthySpend = [80, 100, 120, 150, 180, 200, 250, 300, 350, 400, 450, 500];

        // Meal-slot food suggestions (indices into healthy/unhealthy arrays)
        // We cycle through food IDs rather than purely random for readability
        $created = 0;

        foreach ($weeks as $weekIndex => $week) {
            $weeksAgo  = count($weeks) - 1 - $weekIndex;   // 6,5,4,3,2,1,0
            $weekStart = Carbon::now()->startOfWeek()->subWeeks($weeksAgo);

            for ($dayOffset = 0; $dayOffset < 7; $dayOffset++) {
                // skip declared off-days
                if (in_array($dayOffset, $week['daysOff'])) {
                    continue;
                }

                $date      = $weekStart->copy()->addDays($dayOffset)->toDateString();
                // don't seed future dates
                if ($date > now()->toDateString()) {
                    continue;
                }

                $logsToday = rand($week['logsPerDay'][0], $week['logsPerDay'][1]);

                for ($i = 0; $i < $logsToday; $i++) {
                    $isHealthy = (mt_rand(0, 99) / 100) < $week['healthRatio'];

                    if ($isHealthy) {
                        $foodId = $healthyIds[array_rand($healthyIds)];
                        $spend  = $healthySpend[array_rand($healthySpend)];
                    } else {
                        $foodId = $unhealthyIds[array_rand($unhealthyIds)];
                        $spend  = $unhealthySpend[array_rand($unhealthySpend)];
                    }

                    HabitLog::create([
                        'user_id'      => $user->id,
                        'food_item_id' => $foodId,
                        'log_date'     => $date,
                        'quantity'     => rand(1, 3),
                        'amount_spent' => $spend,
                        'notes'        => null,
                    ]);

                    $created++;
                }
            }
        }

        $this->command->info("Created {$created} habit log entries for user #{$user->id} ({$user->name}) across 7 weeks.");
    }
}
