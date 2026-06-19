<?php

namespace App\Console\Commands;

use App\Models\HabitLog;
use App\Models\User;
use App\Models\UserStreak;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class UpdateStreaksCommand extends Command
{
    protected $signature = 'streaks:update {--date=}';
    protected $description = 'Calculate and update user streaks';

    public function handle()
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::now()->subDay();

        User::all()->each(function (User $user) use ($date) {
            $this->updateUserStreaks($user, $date);
        });

        $this->info('Streaks updated for all users.');
    }

    private function updateUserStreaks(User $user, Carbon $date)
    {
        $streak = $user->streak ?? UserStreak::create(['user_id' => $user->id]);
        $logs = $user->habitLogs()
            ->whereDate('log_date', '<=', $date)
            ->orderByDesc('log_date')
            ->with('foodItem')
            ->get();

        // Check if user logged on $date
        $loggedToday = $logs->first()?->log_date->equalTo($date);

        if (!$loggedToday) {
            $streak->update(['logging_streak' => 0]); // Streak broken
            return;
        }

        // Count consecutive logged days backwards from $date
        $consecutive = 1;
        $checkDate = $date->copy()->subDay();
        foreach ($logs->skip(1) as $log) {
            if ($log->log_date->equalTo($checkDate)) {
                $consecutive++;
                $checkDate->subDay();
            } else {
                break;
            }
        }

        // Update logging streak
        $streak->update([
            'logging_streak' => $consecutive,
            'logging_streak_best' => max($streak->logging_streak_best, $consecutive),
            'last_logged_date' => $date,
        ]);

        // Calculate consistency (% of last 30 days logged)
        $last30 = $logs->filter(fn ($l) => $l->log_date->greaterThanOrEqualTo($date->copy()->subDays(29)));
        $consistency = round(($last30->count() / 30) * 100);
        $streak->update(['consistency_score' => $consistency]);

        // Healthy streak: consecutive days (going back from $date) where >50% of meals are healthy
        $logsByDate  = collect($logs)->groupBy(fn ($l) => $l->log_date->toDateString());
        $healthyConsecutive = 0;
        $checkDate   = $date->copy();

        while (true) {
            $dayLogs = $logsByDate->get($checkDate->toDateString());
            if (!$dayLogs || $dayLogs->isEmpty()) {
                break;
            }
            $healthyCount = $dayLogs->filter(fn ($l) => $l->foodItem->category === 'healthy')->count();
            if ($healthyCount / $dayLogs->count() <= 0.5) {
                break;
            }
            $healthyConsecutive++;
            $checkDate->subDay();
        }

        $streak->update([
            'healthy_streak' => $healthyConsecutive,
            'healthy_streak_best' => max($streak->healthy_streak_best, $healthyConsecutive),
        ]);
    }
}
