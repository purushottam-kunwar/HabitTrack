<?php

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\DailyChallengeController;
use App\Http\Controllers\FoodItemController;
use App\Http\Controllers\HabitLogController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\MobileAuthController;
use App\Http\Controllers\MoodLogController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\WaterLogController;
use App\Http\Controllers\WeightLogController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::post('/auth/login', [MobileAuthController::class, 'login']);
Route::post('/auth/register', [MobileAuthController::class, 'register']);

// Protected mobile routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [MobileAuthController::class, 'logout']);

    // Stats & trends
    Route::get('/stats', [StatsController::class, 'getUserStats']);
    Route::get('/trends', [StatsController::class, 'getTrendData']);

    // Food items
    Route::get('/food-items', [FoodItemController::class, 'index']);

    // Habit logs
    Route::get('/habit-logs', [HabitLogController::class, 'index']);
    Route::post('/habit-logs', [HabitLogController::class, 'store']);
    Route::delete('/habit-logs/{habitLog}', [HabitLogController::class, 'destroy']);

    // Water
    Route::get('/water/today', [WaterLogController::class, 'today']);
    Route::post('/water/add', [WaterLogController::class, 'addGlass']);

    // Weight
    Route::get('/weight', [WeightLogController::class, 'recent']);
    Route::post('/weight', [WeightLogController::class, 'store']);

    // Mood
    Route::get('/mood/today', [MoodLogController::class, 'today']);
    Route::post('/mood', [MoodLogController::class, 'store']);

    // Daily challenges
    Route::get('/challenges/today', [DailyChallengeController::class, 'today']);

    // Budget
    Route::get('/budget', [BudgetController::class, 'index']);
    Route::post('/budget', [BudgetController::class, 'update']);

    // Reports
    Route::get('/report/daily', [ReportController::class, 'daily']);
    Route::get('/report/weekly', [ReportController::class, 'weekly']);

    // Leaderboard & social
    Route::get('/leaderboard', [LeaderboardController::class, 'index']);
    Route::get('/users/search', [LeaderboardController::class, 'search']);
    Route::post('/follow/{user}', [LeaderboardController::class, 'follow']);
    Route::delete('/follow/{user}', [LeaderboardController::class, 'unfollow']);
});
