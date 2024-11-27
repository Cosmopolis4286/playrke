<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote')->hourly();

Schedule::command('generate:bingo-rooms')
    ->timezone('America/Caracas')
    ->dailyAt('00:00') // Primera ejecución a las 00:00
    ->dailyAt('12:00'); // Segunda ejecución a las 12:00