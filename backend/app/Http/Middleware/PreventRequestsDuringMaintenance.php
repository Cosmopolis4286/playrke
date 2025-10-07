<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as Middleware;

/**
 * Middleware que bloquea las solicitudes durante el modo de mantenimiento.
 *
 * Laravel lo usa cuando ejecutas:
 *   php artisan down
 *
 * Solo las rutas listadas en $except podrán seguir funcionando.
 */
class PreventRequestsDuringMaintenance extends Middleware
{
    /**
     * URIs que deben estar exentas durante el mantenimiento.
     *
     * Ejemplo: webhooks o endpoints críticos.
     *
     * @var array<int, string>
     */
    protected $except = [
        // 'status', 
        // 'webhook/*',
    ];
}
