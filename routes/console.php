<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('generate:bingo-rooms')
    ->timezone('America/Caracas')
    ->dailyAt('00:00')->dailyAt('03:00')
    ->dailyAt('06:00')->dailyAt('09:00')
    ->dailyAt('12:00')->dailyAt('15:00')
    ->dailyAt('18:00')->dailyAt('21:00');
