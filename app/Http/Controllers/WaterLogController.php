<?php

namespace App\Http\Controllers;

use App\Models\WaterLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class WaterLogController extends Controller
{
    public function today(): JsonResponse
    {
        $user = auth()->user();
        $log  = $this->todayLog($user);

        return response()->json($this->format($log));
    }

    public function addGlass(): JsonResponse
    {
        $user = auth()->user();
        $log  = $this->todayLog($user);

        $log->increment('glass_count');
        $log->increment('amount_ml', 250);
        $log->refresh();

        return response()->json($this->format($log));
    }

    public function removeGlass(): JsonResponse
    {
        $user = auth()->user();
        $log = WaterLog::where('user_id', $user->id)
            ->whereDate('log_date', Carbon::today())
            ->first();

        if ($log && $log->glass_count > 0) {
            $log->decrement('glass_count');
            $log->decrement('amount_ml', 250);
            $log->refresh();
        }

        return response()->json($this->format($log));
    }

    private function todayLog($user): WaterLog
    {
        return WaterLog::where('user_id', $user->id)
            ->whereDate('log_date', Carbon::today())
            ->first()
            ?? WaterLog::create([
                'user_id'     => $user->id,
                'log_date'    => Carbon::today()->toDateString(),
                'amount_ml'   => 0,
                'glass_count' => 0,
            ]);
    }

    private function format(?WaterLog $log): array
    {
        return [
            'glass_count' => $log?->glass_count ?? 0,
            'amount_ml' => $log?->amount_ml ?? 0,
            'target_glasses' => 8,
            'target_ml' => 2000,
            'percent' => min(100, round((($log?->glass_count ?? 0) / 8) * 100)),
        ];
    }
}
