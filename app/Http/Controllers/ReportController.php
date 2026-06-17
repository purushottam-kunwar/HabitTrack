<?php

namespace App\Http\Controllers;

use App\Models\HabitLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function daily(Request $request): JsonResponse
    {
        $date = $request->get('date', today()->toDateString());

        $logs = HabitLog::with('foodItem')
            ->where('user_id', auth()->id())
            ->whereDate('log_date', $date)
            ->get();

        return response()->json($this->buildDailyStats($logs, $date));
    }

    public function weekly(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', now()->startOfWeek()->toDateString());
        $endDate   = $request->get('end_date', now()->endOfWeek()->toDateString());

        $logs = HabitLog::with('foodItem')
            ->where('user_id', auth()->id())
            ->whereBetween('log_date', [$startDate, $endDate])
            ->get();

        $totalItems     = $logs->count();
        $healthyCount   = $logs->filter(fn($l) => $l->foodItem->category === 'healthy')->count();
        $unhealthyCount = $logs->filter(fn($l) => $l->foodItem->category === 'unhealthy')->count();
        $totalSpent     = $logs->sum('amount_spent');
        $healthScore    = $totalItems > 0 ? round(($healthyCount / $totalItems) * 100) : 0;

        $byDay = $logs->groupBy(fn($l) => $l->log_date->toDateString())
            ->map(fn($group) => [
                'healthy'   => $group->filter(fn($l) => $l->foodItem->category === 'healthy')->count(),
                'unhealthy' => $group->filter(fn($l) => $l->foodItem->category === 'unhealthy')->count(),
                'spent'     => $group->sum('amount_spent'),
            ]);

        $spentByCategory = [
            'healthy'   => $logs->filter(fn($l) => $l->foodItem->category === 'healthy')->sum('amount_spent'),
            'unhealthy' => $logs->filter(fn($l) => $l->foodItem->category === 'unhealthy')->sum('amount_spent'),
        ];

        return response()->json([
            'period'            => ['start' => $startDate, 'end' => $endDate],
            'total_items'       => $totalItems,
            'healthy_count'     => $healthyCount,
            'unhealthy_count'   => $unhealthyCount,
            'health_score'      => $healthScore,
            'total_spent'       => $totalSpent,
            'by_day'            => $byDay,
            'spent_by_category' => $spentByCategory,
        ]);
    }

    public static function buildDailyStats($logs, string $date): array
    {
        $totalItems     = $logs->count();
        $healthyCount   = $logs->filter(fn($l) => $l->foodItem->category === 'healthy')->count();
        $unhealthyCount = $logs->filter(fn($l) => $l->foodItem->category === 'unhealthy')->count();
        $totalSpent     = $logs->sum('amount_spent');
        $healthScore    = $totalItems > 0 ? round(($healthyCount / $totalItems) * 100) : 0;

        return [
            'date'            => $date,
            'total_items'     => $totalItems,
            'healthy_count'   => $healthyCount,
            'unhealthy_count' => $unhealthyCount,
            'health_score'    => $healthScore,
            'total_spent'     => $totalSpent,
            'spent_by_category' => [
                'healthy'   => $logs->filter(fn($l) => $l->foodItem->category === 'healthy')->sum('amount_spent'),
                'unhealthy' => $logs->filter(fn($l) => $l->foodItem->category === 'unhealthy')->sum('amount_spent'),
            ],
        ];
    }
}
