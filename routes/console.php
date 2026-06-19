<?php

use Illuminate\Support\Facades\Schedule;

// Daily report: every day at 9 PM
Schedule::command('report:daily')->dailyAt('21:00');

// XP awards: every day at 9:30 PM (after daily report)
Schedule::command('xp:award')->dailyAt('21:30');

// Achievement checks: every day at 9:45 PM (after XP awarded)
Schedule::command('achievements:check')->dailyAt('21:45');

// Streak updates: every day at 10 PM (after achievements)
Schedule::command('streaks:update')->dailyAt('22:00');

// Weekly report: every Sunday at 9 PM
Schedule::command('report:weekly')->weeklyOn(0, '21:00');
