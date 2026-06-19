<?php

namespace App\Http\Controllers;

use App\Models\HabitLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
    public function index(): JsonResponse
    {
        $user  = Auth::user();
        $today = Carbon::today();

        $todaySpent = HabitLog::where('user_id', $user->id)
            ->whereDate('log_date', $today)
            ->sum('amount_spent');

        // Last 7 days spending per day
        $last7 = [];
        for ($i = 6; $i >= 0; $i--) {
            $date  = $today->copy()->subDays($i);
            $spent = HabitLog::where('user_id', $user->id)
                ->whereDate('log_date', $date)
                ->sum('amount_spent');
            $last7[] = [
                'date'  => $date->toDateString(),
                'spent' => round((float) $spent, 2),
            ];
        }

        $budget  = $user->daily_budget ? (float) $user->daily_budget : null;
        $percent = ($budget && $budget > 0)
            ? min(round(($todaySpent / $budget) * 100), 150)
            : null;

        return response()->json([
            'daily_budget' => $budget,
            'today_spent'  => round((float) $todaySpent, 2),
            'percent'      => $percent,
            'remaining'    => $budget !== null ? round($budget - (float) $todaySpent, 2) : null,
            'last_7_days'  => $last7,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'daily_budget' => 'required|numeric|min:1|max:100000',
        ]);

        $user = Auth::user();
        $user->update(['daily_budget' => $data['daily_budget']]);

        return response()->json(['daily_budget' => (float) $user->daily_budget]);
    }
}
