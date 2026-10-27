<?php

namespace App\Http\Middleware;

use App\Services\Auth\ImpersonationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea acciones de dinero real (retiro de wallet, cambio de contraseña)
 * mientras un admin está impersonando a un jugador — la impersonación es
 * para diagnóstico de soporte, no para operar la cuenta del jugador.
 */
class BlockDuringImpersonation
{
    public function __construct(
        private readonly ImpersonationService $impersonationService,
    ) {
    }

    public function handle(Request $request, Closure $next) : Response
    {
        if ($this->impersonationService->isImpersonating($request)) {
            return response()->json([
                'message' => 'Acción no disponible durante una sesión de soporte.',
            ], 403);
        }

        return $next($request);
    }
}
