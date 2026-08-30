<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

/**
 * Confía únicamente en los rangos IP publicados por Cloudflare (edge delante
 * de Traefik en producción — ver docker-compose.prod.yml). Sin esta lista,
 * `$proxies` quedaba vacío y Laravel confiaba en el header X-Forwarded-For
 * de CUALQUIER origen: cualquiera podía falsificar su IP de cliente y así
 * evadir todo rate limiting basado en $request->ip() (ver
 * RateLimiter::for(...) en AppServiceProvider, la mayoría cae a ->ip() para
 * requests sin sesión) y ensuciar los logs de auditoría con IPs falsas.
 *
 * Lista oficial (actualizar si Cloudflare la cambia):
 * https://www.cloudflare.com/ips/
 */
class TrustProxies extends Middleware
{
    /**
     * @var array<int, string>|string|null
     */
    protected $proxies = [
        // IPv4
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
        // IPv6
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    ];

    /**
     * @var int
     */
    protected $headers = Request::HEADER_X_FORWARDED_ALL;
}
