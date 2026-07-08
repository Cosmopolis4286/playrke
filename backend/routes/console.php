<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('check:expired-bingo-rooms')
    ->timezone('UTC')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('bingo:start-pending-draws')
    ->timezone('UTC')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('validate:transactions')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->everyFiveMinutes()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('bingo:retry-failed-prize-payouts')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->everyFiveMinutes()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('bingo:retry-failed-refunds')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->everyFiveMinutes()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('generate:bingo-rooms')
    ->timezone('UTC')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('handle:exchange-rates')
    ->timezone('UTC')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('trivia:start-scheduled-rooms')
    ->timezone('UTC')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule.log'));
