<?php

use Illuminate\Support\Facades\Schedule;

app(Schedule::class)->command('generate:bingo-rooms')
    ->timezone('America/Caracas')
    ->dailyAt('00:00')
    ->dailyAt('06:00')
    ->dailyAt('12:00')
    ->dailyAt('18:00');
