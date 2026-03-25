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
            
            // Cache de permisos para mejorar rendimiento
            $permissions = cache()->remember(
                "user_permissions_{$user->id}",
                now()->addHours(1),
                function () use ($user) {
                    return $user->getAllPermissions()->pluck('name')->toArray();
                }
            );
            
            // Compartir permisos con todas las vistas
            view()->share('userPermissions', $permissions);
            
            // Compartir roles del usuario
            $roles = cache()->remember(
                "user_roles_{$user->id}",
                now()->addHours(1),
                function () use ($user) {
                    return $user->getRoleNames()->toArray();
                }
            );
            
            view()->share('userRoles', $roles);
        }

        return $next($request);
    }
}
