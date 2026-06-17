<?php

namespace App\Console\Commands;

use App\Http\Controllers\ReportController;
use App\Models\HabitLog;
use App\Models\User;
use App\Notifications\DailyFoodReport;
use Illuminate\Console\Command;

class SendDailyFoodReport extends Command
{
    protected $signature = 'report:daily {--date= : Date to report on (YYYY-MM-DD, defaults to today)}';
    protected $description = 'Send daily food habit report to all users';

    public function handle(): void
    {
        $date = $this->option('date') ?? today()->toDateString();

        User::all()->each(function (User $user) use ($date) {
            $logs = HabitLog::with('foodItem')
                ->where('user_id', $user->id)
                ->whereDate('log_date', $date)
                ->get();

            $stats = ReportController::buildDailyStats($logs, $date);
            $user->notify(new DailyFoodReport($stats));
        });

        $this->info("Daily food reports sent for {$date}.");
    }
}
