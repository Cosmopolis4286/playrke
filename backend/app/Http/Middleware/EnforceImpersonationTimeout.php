<?php

namespace App\Http\Middleware;

use App\Services\Auth\ImpersonationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-termina una sesión de soporte (impersonación) que superó
 * ImpersonationService::TIMEOUT_MINUTES — registrado globalmente en el grupo
 * 'web' (bootstrap/app.php) para cubrir cualquier página sin depender de qué
 * ruta específica visite el admin mientras impersona.
 */
class EnforceImpersonationTimeout
{
    public function __construct(
        private readonly ImpersonationService $impersonationService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->impersonationService->checkExpiry($request)) {
            return redirect('/')->with('error', 'Tu sesión de soporte expiró por inactividad. Volviste a tu cuenta.');
        }

        return $next($request);
    }
}
