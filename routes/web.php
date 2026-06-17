<?php

use App\Http\Controllers\FoodItemController;
use App\Http\Controllers\HabitLogController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', fn() => Inertia::render('Dashboard'))->name('dashboard');
    Route::get('/log', fn() => Inertia::render('Log'))->name('log');
    Route::get('/report', fn() => Inertia::render('Report'))->name('report');
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
    });
});

require __DIR__.'/auth.php';
