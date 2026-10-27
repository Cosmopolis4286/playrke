<?php

namespace App\Http\Middleware;

use App\Services\TosService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea operaciones financieras (wallet, trivia con entrada) hasta que
 * el usuario haya aceptado la versión vigente de los Términos de Servicio.
 *
 * Responde 403 JSON con `tos_required: true` para que el frontend pueda
 * mostrar el modal de aceptación antes de reintentar la operación.
 */
class RequireTosAcceptance
{
    public function __construct(private readonly TosService $tosService)
    {
    }

    public function handle(Request $request, Closure $next) : Response
    {
        $user = $request->user();

        if ($user && $this->tosService->needsAcceptance($user)) {
            return response()->json([
                'success' => false,
                'tos_required' => true,
                'message' => 'Debes aceptar los Términos de Servicio para continuar.',
                'tos_version' => $this->tosService->currentVersionString(),
                'tos_url' => '/tos/current',
            ], 403);
        }

        return $next($request);
    }
}
