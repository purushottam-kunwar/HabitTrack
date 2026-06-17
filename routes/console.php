<?php

use Illuminate\Support\Facades\Schedule;

// Daily report: every day at 9 PM
Schedule::command('report:daily')->dailyAt('21:00');

// Weekly report: every Sunday at 9 PM
Schedule::command('report:weekly')->weeklyOn(0, '21:00');
