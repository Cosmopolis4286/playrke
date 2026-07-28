<?php

use App\Http\Controllers\Games\GameController;
use App\Http\Controllers\Games\Common\GameModeController;

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

// ====================================
// MODOS DE JUEGO (GAME MODES) — catálogo dinámico por juego
// ====================================
route_group(
    'game-modes',
    'game-modes.',
    ['auth', 'role:superadmin'],
    GameModeController::class,
    [
        'fetchList'    => ['method' => 'get', 'uri' => 'by-game/{game}'],
        'store'        => ['method' => 'post', 'uri' => ''],
        'update'       => ['method' => 'put', 'uri' => '{gameMode}'],
        'toggleStatus' => ['method' => 'put', 'uri' => '{gameMode}/status'],
        'destroy'      => ['method' => 'delete', 'uri' => '{gameMode}'],
    ]
);
