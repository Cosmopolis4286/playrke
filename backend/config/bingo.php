<?php

return [
    /*
     * Pausa (segundos) entre el fin de una ronda y el inicio de la siguiente en
     * salas de varias rondas: los jugadores ven el resultado y una cuenta
     * regresiva antes de que empiecen las fichas de la ronda nueva.
     */
    'round_pause_seconds' => (int) env('BINGO_ROUND_PAUSE_SECONDS', 30),

    /*
     * Solo APP_ENV=local: segundos entre fichas cuando la sala no define
     * time_chip (0 = sin pausa). Antes se leía con env() desde el servicio,
     * que no funciona con la configuración cacheada.
     */
    'dev_chip_interval_seconds' => (int) env('BINGO_DEV_INTERVAL_SECONDS', 3),
];
