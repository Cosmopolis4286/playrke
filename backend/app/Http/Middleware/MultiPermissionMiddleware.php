<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
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
            Log::debug('MultiPermissionMiddleware: No user authenticated');
            return redirect()->route('login')->with('error', 'Por favor, inicie sesión para continuar.');
        }

        Log::debug('MultiPermissionMiddleware: Checking user ' . $user->id . ' for permissions: ' . implode(', ', $permissions));
        Log::debug('MultiPermissionMiddleware: User roles: ' . $user->getRoleNames()->implode(', '));
        Log::debug('MultiPermissionMiddleware: User permissions: ' . $user->getPermissionNames()->implode(', '));

        // Verificar si el usuario tiene alguno de los permisos requeridos
        foreach ($permissions as $permission) {
            try {
                // Usar checkPermissionTo que no lanza excepción
                if ($user->checkPermissionTo($permission, 'web')) {
                    Log::debug('MultiPermissionMiddleware: User has permission ' . $permission . ' - ACCESS GRANTED');
                    return $next($request);
                }
            } catch (PermissionDoesNotExist $e) {
                Log::warning('MultiPermissionMiddleware: Permission ' . $permission . ' does not exist in database');
                continue;
            } catch (\Exception $e) {
                Log::error('MultiPermissionMiddleware: Error checking permission ' . $permission . ': ' . $e->getMessage());
                continue;
            }
        }

        Log::debug('MultiPermissionMiddleware: User does not have any required permissions - ACCESS DENIED');

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        return redirect()->route('dashboard')->with('error', 'Acceso denegado.');
    }
}
