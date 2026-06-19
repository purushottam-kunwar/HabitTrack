<?php

use App\Http\Controllers\AiCoachController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\MoodLogController;
use App\Http\Controllers\DailyChallengeController;
use App\Http\Controllers\FoodItemController;
use App\Http\Controllers\HabitLogController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\WaterLogController;
use App\Http\Controllers\WeightLogController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', fn() => Inertia::render('Dashboard'))->name('dashboard');
    Route::get('/log', fn() => Inertia::render('Log'))->name('log');
    Route::get('/report', fn() => Inertia::render('Report'))->name('report');
    Route::get('/leaderboard', fn() => Inertia::render('Leaderboard'))->name('leaderboard');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // API-style routes (JSON)
    Route::prefix('api')->group(function () {
        Route::get('/food-items', [FoodItemController::class, 'index']);
        Route::get('/habit-logs', [HabitLogController::class, 'index']);
        Route::post('/habit-logs', [HabitLogController::class, 'store']);
        Route::delete('/habit-logs/{habitLog}', [HabitLogController::class, 'destroy']);
        Route::get('/report/daily', [ReportController::class, 'daily']);
        Route::get('/report/weekly', [ReportController::class, 'weekly']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::get('/stats', [StatsController::class, 'getUserStats']);
        Route::get('/trends', [StatsController::class, 'getTrendData']);
        Route::get('/challenges/today', [DailyChallengeController::class, 'today']);
        Route::get('/water/today', [WaterLogController::class, 'today']);
        Route::post('/water/add', [WaterLogController::class, 'addGlass']);
        Route::delete('/water/remove', [WaterLogController::class, 'removeGlass']);
        Route::get('/weight', [WeightLogController::class, 'recent']);
        Route::post('/weight', [WeightLogController::class, 'store']);
        Route::get('/ai-coach/daily', [AiCoachController::class, 'daily']);
        Route::get('/mood/today', [MoodLogController::class, 'today']);
        Route::post('/mood', [MoodLogController::class, 'store']);
        Route::get('/mood/history', [MoodLogController::class, 'history']);
        Route::get('/budget', [BudgetController::class, 'index']);
        Route::post('/budget', [BudgetController::class, 'update']);
        Route::get('/leaderboard', [LeaderboardController::class, 'index']);
        Route::get('/users/search', [LeaderboardController::class, 'search']);
        Route::post('/follow/{user}', [LeaderboardController::class, 'follow']);
        Route::delete('/follow/{user}', [LeaderboardController::class, 'unfollow']);
    });
});

require __DIR__.'/auth.php';
