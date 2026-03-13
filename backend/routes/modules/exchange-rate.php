<?php

use App\Http\Controllers\ExchangeRateController;

// ====================================
// TASAS DE CAMBIO
// ====================================
route_group(
    'exchange-rate',
    'exchange-rate.',
    ['auth'],
    ExchangeRateController::class,
    [
        'index'        => ['method' => 'get', 'uri' => '', 'defaults' => ['inertiaOnly' => false]],
        'store'        => ['method' => 'post', 'uri' => ''],
        'updateAll'    => ['method' => 'put', 'uri' => 'update-all'],
        'getRawRates' => ['method' => 'get', 'uri' => 'raw-rates'],
        'fetchList'    => ['method' => 'get', 'uri' => 'fetch-list'],
        'historical'   => ['method' => 'get', 'uri' => 'historical/{id}'],
        'show'         => ['method' => 'get', 'uri' => '{id}'],
        'update'       => ['method' => 'put', 'uri' => '{id}'],
        'delete'       => ['method' => 'put', 'uri' => '{id}/delete'],
        'destroy'      => ['method' => 'delete', 'uri' => '{id}'],
        'toggleStatus' => ['method' => 'put', 'uri' => '{id}/status'],
        'enableCronjob'  => ['method' => 'post', 'uri' => 'cronjob/enable'],
        'disableCronjob' => ['method' => 'post', 'uri' => 'cronjob/disable'],
        'getAutoUpdateStatus' => ['method' => 'get', 'uri' => 'cronjob/status'],
    ]
);

route_public(
    'exchange-rate/public',
    'exchange-rate.public.',
    ExchangeRateController::class,
    [
        'index' => ['method' => 'get', 'uri' => ''],
    ],
    'exchange_rates:read'
);
