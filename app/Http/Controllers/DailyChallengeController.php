<?php

namespace App\Http\Controllers;

use App\Models\DailyChallenge;
use App\Models\UserDailyChallenge;
use App\Models\UserXp;
use App\Models\UserXpLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class DailyChallengeController extends Controller
{
    public function today(): JsonResponse
    {
        $user = auth()->user();
        $today = Carbon::today()->toDateString();

        $challenges = DailyChallenge::all();
        $logs = $user->habitLogs()
            ->whereDate('log_date', $today)
            ->with('foodItem')
            ->get();

        $waterLog = $user->waterLogs()->whereDate('log_date', $today)->first();
        $totalSpent = $logs->sum('amount_spent');
        $glassCount = $waterLog?->glass_count ?? 0;

        $result = $challenges->map(function (DailyChallenge $challenge) use ($user, $today, $logs, $totalSpent, $glassCount) {
            $udc = UserDailyChallenge::firstOrCreate(
                ['user_id' => $user->id, 'daily_challenge_id' => $challenge->id, 'challenge_date' => $today],
                ['current_progress' => 0, 'completed' => false]
            );

            [$progress, $completed] = $this->evaluate($challenge, $logs, $totalSpent, $glassCount);

            if ($completed && !$udc->completed) {
                $udc->update(['current_progress' => $progress, 'completed' => true, 'completed_at' => now()]);
                $this->awardXp($user, $challenge);
            } elseif (!$completed) {
                $udc->update(['current_progress' => $progress]);
            }

            return [
                'id' => $challenge->id,
                'slug' => $challenge->slug,
                'name' => $challenge->name,
                'description' => $challenge->description,
                'category' => $challenge->category,
                'xp_reward' => $challenge->xp_reward,
                'daily_target' => $challenge->daily_target,
                'unit' => $challenge->unit,
                'current_progress' => $progress,
                'completed' => $udc->fresh()->completed,
            ];
        });

        return response()->json($result);
    }

    private function evaluate(DailyChallenge $challenge, $logs, float $totalSpent, int $glassCount): array
    {
        return match ($challenge->slug) {
            'avoid-softdrinks' => $this->evalAvoidSoftDrinks($logs),
            'eat-fruits'       => $this->evalEatFruits($logs),
            'drink-water'      => $this->evalDrinkWater($glassCount),
            'no-junk'          => $this->evalNoJunk($logs),
            'budget-day'       => $this->evalBudget($totalSpent),
            default            => [0, false],
        };
    }

    private function evalAvoidSoftDrinks($logs): array
    {
        $hasSoftDrinks = $logs->contains(fn ($l) =>
            str_contains(strtolower($l->foodItem->name ?? ''), 'cola') ||
            str_contains(strtolower($l->foodItem->name ?? ''), 'soda') ||
            str_contains(strtolower($l->foodItem->name ?? ''), 'pepsi') ||
            str_contains(strtolower($l->foodItem->name ?? ''), 'fanta') ||
            str_contains(strtolower($l->foodItem->name ?? ''), 'sprite') ||
            ($l->foodItem->category === 'drinks' && $l->foodItem->is_healthy === false)
        );
        $progress = $hasSoftDrinks ? 0 : 1;
        return [$progress, !$hasSoftDrinks && $logs->isNotEmpty()];
    }

    private function evalEatFruits($logs): array
    {
        $fruits = $logs->filter(fn ($l) =>
            str_contains(strtolower($l->foodItem->name ?? ''), 'fruit') ||
            str_contains(strtolower($l->foodItem->name ?? ''), 'apple') ||
            str_contains(strtolower($l->foodItem->name ?? ''), 'banana') ||
            str_contains(strtolower($l->foodItem->name ?? ''), 'mango') ||
            str_contains(strtolower($l->foodItem->name ?? ''), 'orange')
        );
        $count = $fruits->sum('quantity');
        return [(int) $count, $count >= 2];
    }

    private function evalDrinkWater(int $glassCount): array
    {
        return [$glassCount, $glassCount >= 8];
    }

    private function evalNoJunk($logs): array
    {
        $hasJunk = $logs->contains(fn ($l) => $l->foodItem->type === 'junk');
        $progress = $hasJunk ? 0 : 1;
        return [$progress, !$hasJunk && $logs->isNotEmpty()];
    }

    private function evalBudget(float $totalSpent): array
    {
        $progress = (int) $totalSpent;
        return [$progress, $totalSpent > 0 && $totalSpent < 300];
    }

    private function awardXp($user, DailyChallenge $challenge): void
    {
        if ($challenge->xp_reward <= 0) {
            return;
        }
        $xp = $user->xp ?? UserXp::create(['user_id' => $user->id]);
        $newTotal = $xp->total_xp + $challenge->xp_reward;
        $xp->update([
            'total_xp' => $newTotal,
            'current_level' => UserXp::levelFromXp($newTotal),
            'xp_in_level' => UserXp::xpInLevelFromTotal($newTotal),
        ]);
        UserXpLog::create([
            'user_id' => $user->id,
            'xp_amount' => $challenge->xp_reward,
            'reason' => 'challenge_' . $challenge->slug,
            'log_date' => today(),
        ]);
    }
}
