<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('generate:bingo-rooms')
    ->timezone('America/Caracas')
    ->everyThreeHours()  // Esto ejecutará el comando cada 3 horas
    ->at('00:00')  // Esto asegura que el primer ejecución será a las 00:00
    ->withoutOverlapping();

// Schedule::command('validate:transactions')
//     ->timezone('America/Caracas')
//     ->everyFiveMinutes();
