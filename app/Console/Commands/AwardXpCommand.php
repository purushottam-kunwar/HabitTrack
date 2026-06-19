<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserXp;
use App\Models\UserXpLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AwardXpCommand extends Command
{
    protected $signature = 'xp:award {--date=}';
    protected $description = 'Award XP for daily actions';

    public function handle()
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::now()->subDay();

        User::all()->each(function (User $user) use ($date) {
            $this->awardXpForUser($user, $date);
        });

        $this->info('XP awarded for all users.');
    }

    private function awardXpForUser(User $user, Carbon $date)
    {
        $logs = $user->habitLogs()
            ->whereDate('log_date', $date)
            ->with('foodItem')
            ->get();

        if ($logs->isEmpty()) {
            return;
        }

        $xp = $user->xp ?? UserXp::create(['user_id' => $user->id]);

        // Award +5 per log
        $xpEarned = $logs->count() * 5;

        // Award +10 per healthy meal
        $healthy = $logs->filter(fn ($l) => $l->foodItem->category === 'healthy');
        $xpEarned += $healthy->count() * 10;

        // Award +20 for completing the day (logged anything)
        $xpEarned += 20;

        // Award +50 if >80% healthy
        $healthPercent = $logs->count() > 0 ? ($healthy->count() / $logs->count()) * 100 : 0;
        if ($healthPercent >= 80) {
            $xpEarned += 50;
        }

        // Update XP
        $newTotal = $xp->total_xp + $xpEarned;
        $newLevel = UserXp::levelFromXp($newTotal);
        $newXpInLevel = UserXp::xpInLevelFromTotal($newTotal);

        $xp->update([
            'total_xp' => $newTotal,
            'current_level' => $newLevel,
            'xp_in_level' => $newXpInLevel,
        ]);

        // Log XP event
        UserXpLog::create([
            'user_id' => $user->id,
            'xp_amount' => $xpEarned,
            'reason' => $healthPercent >= 80 ? '80pct_day' : 'complete_day',
            'log_date' => $date,
        ]);
    }
}
