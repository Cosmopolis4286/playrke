<?php

use App\Http\Controllers\CurrencyController;

// ====================================
// MONEDAS (CURRENCIES)
// ====================================
route_group(
    'currency',
    'currency.',
    ['auth', 'role:superadmin,admin'],
    CurrencyController::class,
    [
        'index'        => ['method' => 'get', 'uri' => '', 'defaults' => ['inertiaOnly' => false]],
        'store'        => ['method' => 'post', 'uri' => ''],
        'fetchList'    => ['method' => 'get', 'uri' => 'fetch-list'],
        'show'         => ['method' => 'get', 'uri' => '{id}'],
        'update'       => ['method' => 'put', 'uri' => '{id}'],
        'delete'       => ['method' => 'put', 'uri' => '{id}/delete'],
        'toggleStatus' => ['method' => 'put', 'uri' => '{id}/status'],
    ]
);
