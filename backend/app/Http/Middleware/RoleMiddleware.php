<?php

namespace App\Http\Middleware;

use App\Services\Auth\RoleAuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para validar roles del usuario.
 *
 * Verifica que el usuario autenticado posea al menos uno de los roles
 * permitidos, vía RoleAuthorizationService (fuente de verdad única).
 */
class RoleMiddleware
{
    public function __construct(
        private readonly RoleAuthorizationService $roleAuthorizationService,
    ) {}

    /**
     * Maneja la petición entrante.
     *
     * @param Request $request
     * @param Closure $next
     * @param mixed ...$roles Prefijos o nombres de roles permitidos
     * @return Response
     */
    public function handle(Request $request, Closure $next, mixed ...$roles): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $this->unauthorizedResponse($request);
        }

        if (! $this->roleAuthorizationService->hasAnyRole($user, (array) $roles)) {
            return $this->forbiddenResponse($request);
        }

        return $next($request);
    }

    /**
     * Respuesta para usuario no autenticado.
     */
    protected function unauthorizedResponse(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'No autenticado.'
            ], 401);
        }

        return redirect()
            ->route('login')
            ->with('error', 'Debe iniciar sesión para continuar.');
    }

    /**
     * Respuesta para acceso denegado.
     */
    protected function forbiddenResponse(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Acceso denegado.'
            ], 403);
        }

        return redirect()
            ->route('dashboard')
            ->with('error', 'No tiene permisos para acceder a este recurso.');
    }
}
