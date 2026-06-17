<?php

namespace App\Console\Commands;

use App\Models\HabitLog;
use App\Models\User;
use App\Notifications\WeeklyFoodReport;
use Illuminate\Console\Command;

class SendWeeklyFoodReport extends Command
{
    protected $signature = 'report:weekly {--start= : Week start date (YYYY-MM-DD)} {--end= : Week end date (YYYY-MM-DD)}';
    protected $description = 'Send weekly food habit report to all users';

    public function handle(): void
    {
        $startDate = $this->option('start') ?? now()->startOfWeek()->toDateString();
        $endDate   = $this->option('end')   ?? now()->endOfWeek()->toDateString();

        User::all()->each(function (User $user) use ($startDate, $endDate) {
            $logs = HabitLog::with('foodItem')
                ->where('user_id', $user->id)
                ->whereBetween('log_date', [$startDate, $endDate])
                ->get();

            $total          = $logs->count();
            $healthyCount   = $logs->filter(fn($l) => $l->foodItem->category === 'healthy')->count();
            $unhealthyCount = $logs->filter(fn($l) => $l->foodItem->category === 'unhealthy')->count();
            $totalSpent     = $logs->sum('amount_spent');
            $healthScore    = $total > 0 ? round(($healthyCount / $total) * 100) : 0;

            $stats = [
                'period'          => ['start' => $startDate, 'end' => $endDate],
                'total_items'     => $total,
                'healthy_count'   => $healthyCount,
                'unhealthy_count' => $unhealthyCount,
                'health_score'    => $healthScore,
                'total_spent'     => $totalSpent,
                'spent_by_category' => [
                    'healthy'   => $logs->filter(fn($l) => $l->foodItem->category === 'healthy')->sum('amount_spent'),
                    'unhealthy' => $logs->filter(fn($l) => $l->foodItem->category === 'unhealthy')->sum('amount_spent'),
                ],
            ];

            $user->notify(new WeeklyFoodReport($stats));
        });

        $this->info("Weekly food reports sent for {$startDate} to {$endDate}.");
    }
}
