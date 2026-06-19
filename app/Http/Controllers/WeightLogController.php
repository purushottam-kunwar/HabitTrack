<?php

namespace App\Http\Controllers;

use App\Models\WeightLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class WeightLogController extends Controller
{
    public function recent(): JsonResponse
    {
        $user = auth()->user();
        $logs = WeightLog::where('user_id', $user->id)
            ->orderByDesc('log_date')
            ->take(30)
            ->get(['log_date', 'weight_kg', 'notes']);

        $current = $logs->first()?->weight_kg;
        $oldest = $logs->last()?->weight_kg;
        $change = ($current && $oldest && $logs->count() > 1)
            ? round((float) $current - (float) $oldest, 1)
            : null;

        return response()->json([
            'logs' => $logs,
            'current_weight' => $current,
            'change_30d' => $change,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'weight_kg' => 'required|numeric|min:20|max:300',
            'notes' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        $log  = WeightLog::where('user_id', $user->id)
            ->whereDate('log_date', Carbon::today())
            ->first();

        if ($log) {
            $log->update(['weight_kg' => $request->weight_kg, 'notes' => $request->notes]);
        } else {
            $log = WeightLog::create([
                'user_id'   => $user->id,
                'log_date'  => Carbon::today()->toDateString(),
                'weight_kg' => $request->weight_kg,
                'notes'     => $request->notes,
            ]);
        }

        return response()->json(['weight_kg' => $log->weight_kg, 'log_date' => $log->log_date]);
    }
}
