<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaderboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $me = Auth::user();
        $filter = $request->query('filter', 'global');

        $followingIds = $me->following()->pluck('users.id')->toArray();

        $query = User::with(['xp', 'streak']);

        if ($filter === 'friends') {
            $ids = array_merge($followingIds, [$me->id]);
            $query->whereIn('id', $ids);
        }

        $users = $query->get()->map(function (User $user) use ($me, $followingIds) {
            return [
                'id'             => $user->id,
                'name'           => $user->name,
                'initials'       => $this->initials($user->name),
                'level'          => $user->xp?->current_level ?? 0,
                'total_xp'       => $user->xp?->total_xp ?? 0,
                'logging_streak' => $user->streak?->logging_streak ?? 0,
                'consistency'    => $user->streak?->consistency_score ?? 0,
                'is_me'          => $user->id === $me->id,
                'is_following'   => in_array($user->id, $followingIds),
            ];
        })
        ->sortByDesc('total_xp')
        ->values();

        return response()->json($users);
    }

    public function search(Request $request): JsonResponse
    {
        $q = trim($request->query('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $me = Auth::user();
        $followingIds = $me->following()->pluck('users.id')->toArray();

        $users = User::where('id', '!=', $me->id)
            ->where('name', 'like', "%{$q}%")
            ->with(['xp', 'streak'])
            ->limit(10)
            ->get()
            ->map(function (User $user) use ($followingIds) {
                return [
                    'id'           => $user->id,
                    'name'         => $user->name,
                    'initials'     => $this->initials($user->name),
                    'level'        => $user->xp?->current_level ?? 0,
                    'total_xp'     => $user->xp?->total_xp ?? 0,
                    'is_following' => in_array($user->id, $followingIds),
                ];
            });

        return response()->json($users);
    }

    public function follow(User $user): JsonResponse
    {
        $me = Auth::user();

        if ($user->id === $me->id) {
            return response()->json(['error' => 'Cannot follow yourself.'], 422);
        }

        $me->following()->syncWithoutDetaching([$user->id]);

        return response()->json(['following' => true]);
    }

    public function unfollow(User $user): JsonResponse
    {
        Auth::user()->following()->detach($user->id);

        return response()->json(['following' => false]);
    }

    private function initials(string $name): string
    {
        $parts = explode(' ', trim($name));
        $initials = strtoupper(substr($parts[0], 0, 1));
        if (isset($parts[1])) {
            $initials .= strtoupper(substr($parts[1], 0, 1));
        }
        return $initials;
    }
}
