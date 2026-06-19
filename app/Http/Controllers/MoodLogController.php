<?php

namespace App\Http\Controllers;

use App\Models\MoodLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class MoodLogController extends Controller
{
    public function today(): JsonResponse
    {
        $log = MoodLog::where('user_id', Auth::id())
            ->whereDate('log_date', Carbon::today())
            ->first();

        return response()->json($log);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mood'         => 'required|integer|min:1|max:5',
            'energy_level' => 'required|integer|min:1|max:5',
            'notes'        => 'nullable|string|max:300',
        ]);

        $log = MoodLog::where('user_id', Auth::id())
            ->whereDate('log_date', Carbon::today())
            ->first();

        if ($log) {
            $log->update($data);
        } else {
            $log = MoodLog::create(array_merge($data, [
                'user_id'  => Auth::id(),
                'log_date' => Carbon::today()->toDateString(),
            ]));
        }

        return response()->json($log);
    }

    public function history(Request $request): JsonResponse
    {
        $days = min((int) $request->query('days', 30), 90);

        $logs = MoodLog::where('user_id', Auth::id())
            ->where('log_date', '>=', Carbon::today()->subDays($days - 1))
            ->orderBy('log_date')
            ->get(['log_date', 'mood', 'energy_level']);

        $avgMood   = $logs->avg('mood');
        $avgEnergy = $logs->avg('energy_level');

        return response()->json([
            'history'    => $logs,
            'avg_mood'   => $avgMood ? round($avgMood, 1) : null,
            'avg_energy' => $avgEnergy ? round($avgEnergy, 1) : null,
        ]);
    }
}
