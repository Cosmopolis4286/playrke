<?php

namespace App\Http\Middleware;

use App\Services\Auth\RoleAuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para verificar múltiples permisos (cualquiera de ellos).
 *
 * Uso: multiPermission:permiso1,permiso2,permiso3
 * El usuario solo necesita tener AL MENOS UNO de los permisos listados.
 * El superadmin siempre pasa (ver RoleAuthorizationService::isSuperadmin()).
 */
class MultiPermissionMiddleware
{
    public function __construct(
        private readonly RoleAuthorizationService $roleAuthorizationService,
    ) {}

    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Por favor, inicie sesión para continuar.');
        }

        if ($this->roleAuthorizationService->hasAnyPermission($user, $permissions)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        return redirect()->route('dashboard')->with('error', 'Acceso denegado.');
    }
}
