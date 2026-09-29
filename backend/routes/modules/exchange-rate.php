<?php

use App\Http\Controllers\ExchangeRateController;

// ====================================
// TASAS DE CAMBIO
// ====================================
// exchange.manage por acción de escritura (antes exchange.view,exchange.manage
// en TODAS las rutas del grupo — un rol con solo exchange.view, pensado para
// consulta de solo lectura, podía igualmente crear/editar/eliminar/activar
// cronjobs, porque multiPermission acepta cualquiera de la lista). Las
// rutas de solo lectura (index/show/fetchList/historical/getRawRates/
// getBcvRate/los 3 *Status) se quedan con view,manage — cualquiera de los
// dos permisos basta para consultar.
route_group(
    'exchange-rate',
    'exchange-rate.',
    ['auth', 'multiPermission:exchange.view,exchange.manage'],
    ExchangeRateController::class,
    [
        'index'        => ['method' => 'get', 'uri' => '', 'defaults' => ['inertiaOnly' => false]],
        'store'        => ['method' => 'post', 'uri' => '', 'middleware' => 'multiPermission:exchange.manage'],
        'updateAll'    => ['method' => 'put', 'uri' => 'update-all', 'middleware' => 'multiPermission:exchange.manage'],
        'getRawRates' => ['method' => 'get', 'uri' => 'raw-rates'],
        'fetchList'    => ['method' => 'get', 'uri' => 'fetch-list'],
        'historical'   => ['method' => 'get', 'uri' => 'historical/{id}'],
        'show'         => ['method' => 'get', 'uri' => '{id}'],
        'update'       => ['method' => 'put', 'uri' => '{id}', 'middleware' => 'multiPermission:exchange.manage'],
        'delete'       => ['method' => 'put', 'uri' => '{id}/delete', 'middleware' => 'multiPermission:exchange.manage'],
        'destroy'      => ['method' => 'delete', 'uri' => '{id}', 'middleware' => 'multiPermission:exchange.manage'],
        'toggleStatus' => ['method' => 'put', 'uri' => '{id}/status', 'middleware' => 'multiPermission:exchange.manage'],
        'enableCronjob'  => ['method' => 'post', 'uri' => 'cronjob/enable', 'middleware' => 'multiPermission:exchange.manage'],
        'disableCronjob' => ['method' => 'post', 'uri' => 'cronjob/disable', 'middleware' => 'multiPermission:exchange.manage'],
        'getAutoUpdateStatus' => ['method' => 'get', 'uri' => 'cronjob/status'],
        'getBcvRate' => ['method' => 'get', 'uri' => 'bcv-rate'],
        'enableBcvAutoUpdate'  => ['method' => 'post', 'uri' => 'bcv-cronjob/enable', 'middleware' => 'multiPermission:exchange.manage'],
        'disableBcvAutoUpdate' => ['method' => 'post', 'uri' => 'bcv-cronjob/disable', 'middleware' => 'multiPermission:exchange.manage'],
        'getBcvAutoUpdateStatus' => ['method' => 'get', 'uri' => 'bcv-cronjob/status'],
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
