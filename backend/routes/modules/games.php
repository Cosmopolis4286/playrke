<?php

use App\Http\Controllers\Games\GameController;

// ====================================
// JUEGOS (GAMES)
// ====================================
route_group(
    'games',
    'games.',
    ['auth'],
    GameController::class,
    [
        'index'        => ['method' => 'get', 'uri' => ''],
        'store'        => ['method' => 'post', 'uri' => ''],
        'fetchList'    => ['method' => 'get', 'uri' => 'fetch-list'],
        'show'         => ['method' => 'get', 'uri' => '{id}'],
        'update'       => ['method' => 'put', 'uri' => '{id}'],
        'delete'       => ['method' => 'put', 'uri' => '{id}/delete'],
        'toggleStatus' => ['method' => 'put', 'uri' => '{id}/status'],
    ]
);
