<?php

use App\Http\Controllers\ExchangeRate\NetworkExchangeRateController;
use App\Http\Controllers\ExchangeRateController;
use Illuminate\Support\Facades\Route;

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
        // route_group() registra en el orden literal de este array y
        // Laravel resuelve por primer-match — TODAS las rutas literales
        // (raw-rates, bcv-rate, cronjob/*, etc.) deben ir ANTES que 'show'
        // ({id} genérico) o quedan capturadas por él como si "bcv-rate"
        // fuera un id (bug real: GET /exchange-rate/bcv-rate devolvía 404
        // porque show('bcv-rate') no encontraba ninguna tasa con ese id).
        'index' => ['method' => 'get', 'uri' => '', 'defaults' => ['inertiaOnly' => false]],
        'store' => ['method' => 'post', 'uri' => '', 'middleware' => 'multiPermission:exchange.manage'],
        'updateAll' => ['method' => 'put', 'uri' => 'update-all', 'middleware' => 'multiPermission:exchange.manage'],
        'getRawRates' => ['method' => 'get', 'uri' => 'raw-rates'],
        'fetchList' => ['method' => 'get', 'uri' => 'fetch-list'],
        'historical' => ['method' => 'get', 'uri' => 'historical/{id}'],
        'exportHistorical' => ['method' => 'get', 'uri' => 'historical/{id}/export'],
        'enableCronjob' => ['method' => 'post', 'uri' => 'cronjob/enable', 'middleware' => 'multiPermission:exchange.manage'],
        'disableCronjob' => ['method' => 'post', 'uri' => 'cronjob/disable', 'middleware' => 'multiPermission:exchange.manage'],
        'getAutoUpdateStatus' => ['method' => 'get', 'uri' => 'cronjob/status'],
        'getBcvRate' => ['method' => 'get', 'uri' => 'bcv-rate'],
        'enableBcvAutoUpdate' => ['method' => 'post', 'uri' => 'bcv-cronjob/enable', 'middleware' => 'multiPermission:exchange.manage'],
        'disableBcvAutoUpdate' => ['method' => 'post', 'uri' => 'bcv-cronjob/disable', 'middleware' => 'multiPermission:exchange.manage'],
        'getBcvAutoUpdateStatus' => ['method' => 'get', 'uri' => 'bcv-cronjob/status'],
        // Auto-update por moneda individual (todas excepto VES, que usa
        // bcv-cronjob/* arriba con la fuente oficial del BCV) — control
        // independiente del switch global de Index.vue, ver
        // ExchangeRateService::enableCurrencyAutoUpdate().
        'enableCurrencyAutoUpdate' => ['method' => 'post', 'uri' => 'currency-cronjob/{isoCode}/enable', 'middleware' => 'multiPermission:exchange.manage'],
        'disableCurrencyAutoUpdate' => ['method' => 'post', 'uri' => 'currency-cronjob/{isoCode}/disable', 'middleware' => 'multiPermission:exchange.manage'],
        'getCurrencyAutoUpdateStatus' => ['method' => 'get', 'uri' => 'currency-cronjob/{isoCode}/status'],
        // A partir de aquí, solo rutas con {id} genérico.
        'show' => ['method' => 'get', 'uri' => '{id}'],
        'update' => ['method' => 'put', 'uri' => '{id}', 'middleware' => 'multiPermission:exchange.manage'],
        'delete' => ['method' => 'put', 'uri' => '{id}/delete', 'middleware' => 'multiPermission:exchange.manage'],
        'destroy' => ['method' => 'delete', 'uri' => '{id}', 'middleware' => 'multiPermission:exchange.manage'],
        'toggleStatus' => ['method' => 'put', 'uri' => '{id}/status', 'middleware' => 'multiPermission:exchange.manage'],
    ],
);

route_public(
    'exchange-rate/public',
    'exchange-rate.public.',
    ExchangeRateController::class,
    [
        'index' => ['method' => 'get', 'uri' => ''],
    ],
    'exchange_rates:read',
);

// Tasa vigente para la red del usuario (widget de tableros). Prefijo propio
// (plural) para no chocar con exchange-rate/{id} del grupo de arriba. El
// gate exchange.viewNetwork (NetworkExchangeRatePolicy) además exige rol.
Route::middleware(['auth', 'multiPermission:exchange.view-network'])
    ->get('exchange-rates/network', [NetworkExchangeRateController::class, 'index'])
    ->name('exchange-rates.network');
