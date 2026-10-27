<?php

use App\Http\Controllers\AppParameterController;

// ====================================
// PARÁMETROS DE CONFIGURACIÓN (APP PARAMETERS)
// ====================================
route_group(
    'app-parameters',
    'app-parameters.',
    ['auth'],
    AppParameterController::class,
    [
        'index' => ['method' => 'get', 'uri' => ''],
        'store' => ['method' => 'post', 'uri' => ''],
        'fetchList' => ['method' => 'get', 'uri' => 'fetch-list'],
        'show' => ['method' => 'get', 'uri' => '{id}'],
        'update' => ['method' => 'put', 'uri' => '{id}'],
        'delete' => ['method' => 'put', 'uri' => '{id}/delete'],
        'toggleStatus' => ['method' => 'put', 'uri' => '{id}/status'],
    ],
);
