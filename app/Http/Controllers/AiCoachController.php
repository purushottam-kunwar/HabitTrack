<?php

namespace App\Http\Controllers;

use Anthropic\Client as AnthropicClient;
use App\Models\HabitLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AiCoachController extends Controller
{
    public function daily(): JsonResponse
    {
        $client = app(AnthropicClient::class);
        $user = Auth::user();
        $today = Carbon::today();

        $logs = HabitLog::where('user_id', $user->id)
            ->whereDate('log_date', $today)
            ->with('foodItem')
            ->get();

        if ($logs->isEmpty()) {
            return response()->json([
                'suggestion' => "You haven't logged any food today yet. Start logging to get personalized coaching!",
                'has_data' => false,
            ]);
        }

        $totalSpent = $logs->sum('amount_spent');
        $totalCalories = $logs->sum(fn ($l) => ($l->foodItem->calories ?? 0) * $l->quantity);
        $totalItems = $logs->count();

        $categories = $logs->groupBy(fn ($l) => $l->foodItem->category ?? 'unknown')
            ->map(fn ($group) => $group->count());

        $healthyCount = $categories->get('healthy', 0);
        $unhealthyCount = $categories->get('unhealthy', 0);
        $healthPercent = $totalItems > 0 ? round(($healthyCount / $totalItems) * 100) : 0;

        $itemSummary = $logs->map(fn ($l) => sprintf(
            '%s (×%s, %s kcal, ₹%s)',
            $l->foodItem->name ?? 'Unknown',
            $l->quantity,
            ($l->foodItem->calories ?? 0) * $l->quantity,
            number_format($l->amount_spent, 2)
        ))->join(', ');

        $prompt = <<<PROMPT
You are a friendly and encouraging health coach for a habit tracking app. Analyze today's food log and give concise, actionable advice.

Today's food log:
- Items: {$itemSummary}
- Total calories: {$totalCalories} kcal
- Total spent: ₹{$totalSpent}
- Healthy items: {$healthyCount} of {$totalItems} ({$healthPercent}%)
- Unhealthy items: {$unhealthyCount}

Give 2-3 sentences of personalized coaching. Be specific about what they logged, celebrate wins, gently suggest improvements if needed. Keep it warm, motivating, and under 100 words.
PROMPT;

        try {
            $response = $client->messages->create(
                model: 'claude-opus-4-8',
                maxTokens: 256,
                thinking: ['type' => 'adaptive'],
                messages: [
                    ['role' => 'user', 'content' => $prompt],
                ],
            );

            $suggestion = '';
            foreach ($response->content as $block) {
                if ($block->type === 'text') {
                    $suggestion = $block->text;
                    break;
                }
            }

            return response()->json([
                'suggestion' => $suggestion,
                'has_data' => true,
                'stats' => [
                    'total_calories' => $totalCalories,
                    'total_spent' => $totalSpent,
                    'health_percent' => $healthPercent,
                ],
            ]);
        } catch (\Anthropic\Core\Exceptions\APIStatusException $e) {
            return response()->json([
                'suggestion' => 'AI coach is temporarily unavailable. Keep up your logging streak!',
                'has_data' => true,
                'error' => $e->getMessage(),
            ], 503);
        } catch (\Throwable $e) {
            return response()->json([
                'suggestion' => 'Could not fetch AI coaching right now. Please try again later.',
                'has_data' => true,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
