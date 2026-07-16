<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LoadUserPermissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si el usuario está autenticado, cargar sus permisos en la sesión
        if (auth()->check()) {
            $user = auth()->user();

            // Un solo round-trip de cache en lugar de dos separados (permisos + roles).
            // Ambos se invalidan juntos — esto es correcto porque cambian al mismo tiempo
            // (asignación/revocación de roles siempre afecta ambos).
            $data = cache()->remember(
                "user_perms_roles_{$user->id}",
                now()->addHours(1),
                fn () => [
                    'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                    'roles'       => $user->getRoleNames()->toArray(),
                ]
            );

            view()->share('userPermissions', $data['permissions']);
            view()->share('userRoles',       $data['roles']);
        }

        return $next($request);
    }
}
