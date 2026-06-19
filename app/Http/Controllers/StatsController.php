<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\UserXpLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StatsController extends Controller
{
    public function getUserStats(): JsonResponse
    {
        $user = auth()->user();
        $streak = $user->streak;
        $xp = $user->xp;

        return response()->json([
            'streaks' => [
                'logging' => $streak?->logging_streak ?? 0,
                'logging_best' => $streak?->logging_streak_best ?? 0,
                'healthy' => $streak?->healthy_streak ?? 0,
                'healthy_best' => $streak?->healthy_streak_best ?? 0,
                'consistency' => $streak?->consistency_score ?? 0,
            ],
            'xp' => [
                'total' => $xp?->total_xp ?? 0,
                'level' => $xp?->current_level ?? 1,
                'xp_in_level' => $xp?->xp_in_level ?? 0,
                'xp_to_next_level' => 500,
                'progress_percent' => $xp ? ($xp->xp_in_level / 500) * 100 : 0,
            ],
            'achievements' => $user->achievements()
                ->with('pivot')
                ->get()
                ->map(fn ($a) => [
                    'slug' => $a->slug,
                    'name' => $a->name,
                    'icon' => $a->icon,
                    'category' => $a->category,
                    'unlocked_at' => $a->pivot->unlocked_at->toDateString(),
                ]),
            'recent_xp_logs' => UserXpLog::where('user_id', $user->id)
                ->orderByDesc('log_date')
                ->take(5)
                ->get(['xp_amount', 'reason', 'log_date']),
        ]);
    }

    public function getTrendData(Request $request): JsonResponse
    {
        $user = auth()->user();
        $days = (int) $request->get('days', 30);
        $startDate = Carbon::now()->subDays($days)->toDateString();

        $logs = $user->habitLogs()
            ->where('log_date', '>=', $startDate)
            ->orderBy('log_date')
            ->with('foodItem')
            ->get();

        // Group by date and calculate daily aggregates
        $grouped = $logs->groupBy('log_date')->map(function ($dayLogs) {
            $healthy = $dayLogs->filter(fn ($l) => $l->foodItem->category === 'healthy')->count();
            $total = $dayLogs->count();
            return [
                'health_score' => $total > 0 ? round(($healthy / $total) * 100) : 0,
                'calories' => $dayLogs->sum(fn ($l) => ($l->foodItem->calories ?? 0) * $l->quantity),
                'spending' => (float) $dayLogs->sum('amount_spent'),
            ];
        });

        return response()->json([
            'trend_data' => $grouped->map(fn ($v, $k) => array_merge($v, ['date' => $k])),
            'summary' => [
                'avg_health_score' => (int) $grouped->avg('health_score'),
                'avg_calories' => (int) $grouped->avg('calories'),
                'total_spending' => round($grouped->sum('spending'), 2),
                'total_days_logged' => $grouped->count(),
            ],
        ]);
    }
}
