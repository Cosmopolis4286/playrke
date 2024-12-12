<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('generate:bingo-rooms')
    ->timezone('America/Caracas')
    ->everyThreeHours()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule.log'));

// Schedule::command('validate:transactions')
//     ->timezone('America/Caracas')
//     ->everyFiveMinutes();
