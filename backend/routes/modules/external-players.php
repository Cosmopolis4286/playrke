<?php

use App\Http\Controllers\ExternalPlayerController;

/*
|--------------------------------------------------------------------------
| Rutas de Jugadores Externos
|--------------------------------------------------------------------------
| Rutas para gestionar jugadores externos
*/

// ====================================
// JUGADORES EXTERNOS
// ====================================
route_group('external-players', 'external-players.', ['auth'], ExternalPlayerController::class, [
    'check' => ['method' => 'get', 'uri' => 'check'],
]);
