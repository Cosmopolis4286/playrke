<?php

return [
    /*
     * Pausa (segundos) entre el fin de una ronda y el inicio de la siguiente en
     * salas de varias rondas: los jugadores ven el resultado y una cuenta
     * regresiva antes de que empiecen las fichas de la ronda nueva.
     */
    'round_pause_seconds' => (int) env('BINGO_ROUND_PAUSE_SECONDS', 30),
];
