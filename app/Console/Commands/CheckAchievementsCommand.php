<?php

namespace App\Console\Commands;

use App\Models\Achievement;
use App\Models\User;
use App\Models\UserXp;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckAchievementsCommand extends Command
{
    protected $signature = 'achievements:check {--date=}';
    protected $description = 'Check and unlock achievements for users';

    public function handle()
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::now()->subDay();

        User::all()->each(function (User $user) use ($date) {
            $this->checkAchievements($user, $date);
        });

        $this->info('Achievements checked for all users.');
    }

    private function checkAchievements(User $user, Carbon $date)
    {
        $unlocked = $user->achievements()->pluck('slug')->toArray();
        $streak = $user->streak;
        $xp = $user->xp;
        $totalLogs = $user->habitLogs()->count();

        // first-log: If user has any logs
        if (!in_array('first-log', $unlocked) && $totalLogs > 0) {
            $this->unlockAchievement($user, 'first-log');
        }

        // hundred-meals: If total logs >= 100
        if (!in_array('hundred-meals', $unlocked) && $totalLogs >= 100) {
            $this->unlockAchievement($user, 'hundred-meals');
        }

        // 500-meals: If total logs >= 500
        if (!in_array('500-meals', $unlocked) && $totalLogs >= 500) {
            $this->unlockAchievement($user, '500-meals');
        }

        // 7-day-logging: If logging streak >= 7
        if (!in_array('7-day-logging', $unlocked) && $streak && $streak->logging_streak >= 7) {
            $this->unlockAchievement($user, '7-day-logging');
        }

        // 14-day-logging: If logging streak >= 14
        if (!in_array('14-day-logging', $unlocked) && $streak && $streak->logging_streak >= 14) {
            $this->unlockAchievement($user, '14-day-logging');
        }

        // Shared: this week's logs for healthy-week achievements
        $weekStart = $date->copy()->startOfWeek();
        $weekEnd   = $date->copy()->endOfWeek();
        $weekLogs  = $user->habitLogs()
            ->whereBetween('log_date', [$weekStart, $weekEnd])
            ->with('foodItem')
            ->get();

        if ($weekLogs->isNotEmpty()) {
            $healthyCount  = $weekLogs->filter(fn ($l) => $l->foodItem->category === 'healthy')->count();
            $healthPercent = ($healthyCount / $weekLogs->count()) * 100;

            // first-healthy-week: >70% healthy meals in a week
            if (!in_array('first-healthy-week', $unlocked) && $healthPercent >= 70) {
                $this->unlockAchievement($user, 'first-healthy-week');
            }

            // health-champion: >80% healthy meals in a week
            if (!in_array('health-champion', $unlocked) && $healthPercent >= 80) {
                $this->unlockAchievement($user, 'health-champion');
            }
        }

        // no-softdrinks-7: Avoided soft-drinks for 7 consecutive LOGGED days
        if (!in_array('no-softdrinks-7', $unlocked)) {
            $softDrinkFree = true;
            $daysWithLogs  = 0;
            for ($i = 0; $i < 7; $i++) {
                $dayLogs = $user->habitLogs()
                    ->whereDate('log_date', $date->copy()->subDays($i))
                    ->with('foodItem')
                    ->get();
                if ($dayLogs->isEmpty()) {
                    continue;
                }
                $daysWithLogs++;
                $hasSoftDrinks = $dayLogs->contains(fn ($l) =>
                    stripos($l->foodItem->name ?? '', 'cola') !== false ||
                    stripos($l->foodItem->name ?? '', 'soda') !== false ||
                    stripos($l->foodItem->name ?? '', 'pepsi') !== false ||
                    stripos($l->foodItem->name ?? '', 'fanta') !== false ||
                    stripos($l->foodItem->name ?? '', 'sprite') !== false
                );
                if ($hasSoftDrinks) {
                    $softDrinkFree = false;
                    break;
                }
            }
            if ($softDrinkFree && $daysWithLogs >= 7) {
                $this->unlockAchievement($user, 'no-softdrinks-7');
            }
        }

        // budget-master: Spend ₹100 or less every day for 7 consecutive days (must have logs)
        if (!in_array('budget-master', $unlocked)) {
            $budgetMaster = true;
            $daysWithLogs = 0;
            for ($i = 0; $i < 7; $i++) {
                $dayLogs = $user->habitLogs()
                    ->whereDate('log_date', $date->copy()->subDays($i))
                    ->get();
                if ($dayLogs->isEmpty()) {
                    continue;
                }
                $daysWithLogs++;
                if ((float) $dayLogs->sum('amount_spent') > 100) {
                    $budgetMaster = false;
                    break;
                }
            }
            if ($budgetMaster && $daysWithLogs >= 7) {
                $this->unlockAchievement($user, 'budget-master');
            }
        }

        // consistency-30: If consistency score >= 80%
        if (!in_array('consistency-30', $unlocked) && $streak && $streak->consistency_score >= 80) {
            $this->unlockAchievement($user, 'consistency-30');
        }

        // level-5: If current level >= 5
        if (!in_array('level-5', $unlocked) && $xp && $xp->current_level >= 5) {
            $this->unlockAchievement($user, 'level-5');
        }

        // level-10: If current level >= 10
        if (!in_array('level-10', $unlocked) && $xp && $xp->current_level >= 10) {
            $this->unlockAchievement($user, 'level-10');
        }
    }

    private function unlockAchievement(User $user, string $slug)
    {
        $achievement = Achievement::where('slug', $slug)->first();
        if (!$achievement) {
            return;
        }

        // Check if already unlocked
        if ($user->achievements()->where('achievement_id', $achievement->id)->exists()) {
            return;
        }

        $user->achievements()->attach($achievement->id, ['unlocked_at' => now()]);

        // Award XP from achievement
        $xp = $user->xp ?? UserXp::create(['user_id' => $user->id]);
        $newTotal = $xp->total_xp + $achievement->xp_reward;
        $xp->update([
            'total_xp' => $newTotal,
            'current_level' => UserXp::levelFromXp($newTotal),
            'xp_in_level' => UserXp::xpInLevelFromTotal($newTotal),
        ]);
    }
}
