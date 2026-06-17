<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WeeklyFoodReport extends Notification
{
    use Queueable;

    public function __construct(private array $stats) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $stats = $this->stats;
        $total = $stats['total_items'];
        $healthy = $stats['healthy_count'];
        $unhealthy = $stats['unhealthy_count'];
        $score = $stats['health_score'];
        $spentHealthy = number_format($stats['spent_by_category']['healthy'], 2);
        $spentUnhealthy = number_format($stats['spent_by_category']['unhealthy'], 2);
        $totalSpent = number_format($stats['total_spent'], 2);
        $start = $stats['period']['start'];
        $end = $stats['period']['end'];

        if ($total === 0) {
            $summary = "No food was logged this week ({$start} to {$end}).";
        } else {
            $summary = "Weekly report ({$start} to {$end}): You logged {$total} item(s) — "
                . "{$healthy} healthy and {$unhealthy} unhealthy. "
                . "Health score: {$score}%. "
                . "Spent ₹{$spentHealthy} on healthy food and ₹{$spentUnhealthy} on unhealthy food (total ₹{$totalSpent}).";
        }

        return [
            'type'            => 'weekly_report',
            'period'          => $stats['period'],
            'summary'         => $summary,
            'total_items'     => $total,
            'healthy_count'   => $healthy,
            'unhealthy_count' => $unhealthy,
            'health_score'    => $score,
            'total_spent'     => $stats['total_spent'],
            'spent_by_category' => $stats['spent_by_category'],
        ];
    }
}
