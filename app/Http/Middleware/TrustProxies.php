<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

/**
 * Middleware que configura los proxies confiables de la aplicación.
 *
 * Esto es importante cuando usas load balancers o servidores proxy
 * que modifican los encabezados X-Forwarded-*.
 */
class TrustProxies extends Middleware
{
    /**
     * Los proxies confiables de la aplicación.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;

    /**
     * Los encabezados que se deben confiar de los proxies.
     *
     * @var int
     */
    protected $headers = Request::HEADER_X_FORWARDED_ALL;
}
