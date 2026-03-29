<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para verificar múltiples permisos (cualquiera de ellos).
 *
 * Uso: multiPermission:permiso1,permiso2,permiso3
 * El usuario solo necesita tener AL MENOS UNO de los permisos listados.
 */
class MultiPermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Por favor, inicie sesión para continuar.');
        }

        // Verificar si el usuario tiene alguno de los permisos requeridos
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        return redirect()->route('dashboard')->with('error', 'Acceso denegado.');
    }
}
