<?php

namespace App\Http\Middleware;

use App\Repositories\Users\Roles\RoleUserRepository;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para validar roles activos del usuario.
 *
 * Verifica que el usuario autenticado posea al menos uno
 * de los roles permitidos y que la asignación esté activa
 * (sin expiración o con expiración futura).
 */
class RoleMiddleware
{
    protected RoleUserRepository $roleUserRepository;

    /**
     * Constructor del middleware.
     *
     * @param RoleUserRepository $roleUserRepository
     */
    public function __construct(RoleUserRepository $roleUserRepository)
    {
        $this->roleUserRepository = $roleUserRepository;
    }

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

        if (
            ! $this->roleUserRepository
                ->userHasAnyRole($user, (array) $roles)
        ) {
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
