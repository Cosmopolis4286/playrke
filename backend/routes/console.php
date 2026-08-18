<?php

use App\Support\Scheduling\ScheduledCommandGate;
use Illuminate\Support\Facades\Schedule;

Schedule::command('check:expired-bingo-rooms')
    ->when(fn () => ScheduledCommandGate::isEnabled('check:expired-bingo-rooms'))
    ->timezone('UTC')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('bingo:start-pending-draws')
    ->when(fn () => ScheduledCommandGate::isEnabled('bingo:start-pending-draws'))
    ->timezone('UTC')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('validate:transactions')
    ->when(fn () => ScheduledCommandGate::isEnabled('validate:transactions'))
    ->timezone('UTC')
    ->withoutOverlapping()
    ->everyFiveMinutes()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('bingo:retry-failed-prize-payouts')
    ->when(fn () => ScheduledCommandGate::isEnabled('bingo:retry-failed-prize-payouts'))
    ->timezone('UTC')
    ->withoutOverlapping()
    ->everyFiveMinutes()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('bingo:retry-failed-refunds')
    ->when(fn () => ScheduledCommandGate::isEnabled('bingo:retry-failed-refunds'))
    ->timezone('UTC')
    ->withoutOverlapping()
    ->everyFiveMinutes()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('generate:bingo-rooms')
    ->when(fn () => ScheduledCommandGate::isEnabled('generate:bingo-rooms'))
    ->timezone('UTC')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('handle:exchange-rates')
    ->when(fn () => ScheduledCommandGate::isEnabled('handle:exchange-rates'))
    ->timezone('UTC')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('trivia:start-scheduled-rooms')
    ->when(fn () => ScheduledCommandGate::isEnabled('trivia:start-scheduled-rooms'))
    ->timezone('UTC')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('trivia:auto-finish-stale-games')
    ->when(fn () => ScheduledCommandGate::isEnabled('trivia:auto-finish-stale-games'))
    ->timezone('UTC')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('trivia:retry-failed-prize-payouts')
    ->when(fn () => ScheduledCommandGate::isEnabled('trivia:retry-failed-prize-payouts'))
    ->timezone('UTC')
    ->withoutOverlapping()
    ->everyFiveMinutes()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('audit:prune')
    ->when(fn () => ScheduledCommandGate::isEnabled('audit:prune'))
    ->timezone('UTC')
    ->withoutOverlapping()
    ->daily()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('logs:prune')
    ->when(fn () => ScheduledCommandGate::isEnabled('logs:prune'))
    ->timezone('UTC')
    ->withoutOverlapping()
    ->daily()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('logs:alert-threshold')
    ->when(fn () => ScheduledCommandGate::isEnabled('logs:alert-threshold'))
    ->timezone('UTC')
    ->withoutOverlapping()
    ->everyFifteenMinutes()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('permissions:expire-specific-grants')
    ->when(fn () => ScheduledCommandGate::isEnabled('permissions:expire-specific-grants'))
    ->timezone('UTC')
    ->withoutOverlapping()
    ->everyFiveMinutes()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('permissions:notify-expiring-specific-grants')
    ->when(fn () => ScheduledCommandGate::isEnabled('permissions:notify-expiring-specific-grants'))
    ->timezone('UTC')
    ->withoutOverlapping()
    ->dailyAt('08:00')
    ->appendOutputTo(storage_path('logs/schedule.log'));
