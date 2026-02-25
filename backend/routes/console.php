<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('handle:bingo-rooms')
    ->timezone('America/Caracas')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('bingo-draw:broadcast')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('validate:transactions')
    ->timezone('America/Caracas')
    ->withoutOverlapping()
    ->everyFiveMinutes()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('generate:bingo-rooms')
    ->timezone('America/Caracas')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('handle:exchange-rates')
    ->timezone('America/Caracas')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule.log'));
