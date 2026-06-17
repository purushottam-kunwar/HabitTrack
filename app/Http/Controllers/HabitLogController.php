<?php

namespace App\Http\Controllers;

use App\Models\HabitLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HabitLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $date = $request->get('date', today()->toDateString());

        $logs = HabitLog::with('foodItem')
            ->where('user_id', auth()->id())
            ->whereDate('log_date', $date)
            ->latest()
            ->get();

        return response()->json($logs);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'food_item_id' => ['required', 'exists:food_items,id'],
            'log_date'     => ['required', 'date'],
            'quantity'     => ['required', 'integer', 'min:1', 'max:20'],
            'amount_spent' => ['nullable', 'numeric', 'min:0'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ]);

        $log = HabitLog::create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

        return response()->json($log->load('foodItem'), 201);
    }

    public function destroy(HabitLog $habitLog): JsonResponse
    {
        abort_if($habitLog->user_id !== auth()->id(), 403);
        $habitLog->delete();

        return response()->json(null, 204);
    }
}
