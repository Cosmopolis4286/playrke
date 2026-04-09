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

        // El superadmin tiene acceso total, sin necesidad de permisos explícitos
        if ($user->hasRole('superadmin')) {
            return $next($request);
        }

        Log::debug('MultiPermissionMiddleware: Checking user ' . $user->id . ' for permissions: ' . implode(', ', $permissions));
        Log::debug('MultiPermissionMiddleware: User roles: ' . $user->getRoleNames()->implode(', '));
        
        // Obtener TODOS los permisos incluyendo los heredados de roles
        $allPermissions = $user->getAllPermissions()->pluck('name')->toArray();
        Log::debug('MultiPermissionMiddleware: User all permissions (via roles): ' . implode(', ', $allPermissions));
        
        // Debug: verificar roles con guard específico
        $rolesWithGuard = $user->roles()->get()->map(function($role) {
            return $role->name . ' (' . $role->guard_name . ')';
        })->implode(', ');
        Log::debug('MultiPermissionMiddleware: User roles with guard: ' . $rolesWithGuard);
        
        // Debug: verificar si el usuario tiene el rol admin con guard web
        $hasAdminRole = $user->hasRole('admin');
        Log::debug('MultiPermissionMiddleware: User hasRole(admin): ' . ($hasAdminRole ? 'YES' : 'NO'));
        
        // Debug: cargar permisos directamente desde la BD
        $directPermissions = \DB::table('model_has_permissions')
            ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
            ->where('model_has_permissions.model_id', $user->id)
            ->where('permissions.guard_name', 'web')
            ->pluck('permissions.name')
            ->toArray();
        Log::debug('MultiPermissionMiddleware: Direct user permissions from DB: ' . implode(', ', $directPermissions));
        
        // Debug: permisos vía roles
        $roleIds = $user->roles()->pluck('roles.id')->toArray();
        Log::debug('MultiPermissionMiddleware: User role IDs: ' . implode(', ', $roleIds));
        
        if (!empty($roleIds)) {
            $rolePermissions = \DB::table('role_has_permissions')
                ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
                ->whereIn('role_has_permissions.role_id', $roleIds)
                ->where('permissions.guard_name', 'web')
                ->pluck('permissions.name')
                ->unique()
                ->toArray();
            Log::debug('MultiPermissionMiddleware: Role permissions from DB: ' . implode(', ', $rolePermissions));
        }

        // Verificar si el usuario tiene alguno de los permisos requeridos
        foreach ($permissions as $permission) {
            if (in_array($permission, $allPermissions)) {
                Log::debug('MultiPermissionMiddleware: User has permission ' . $permission . ' - ACCESS GRANTED');
                return $next($request);
            }
        }

        Log::debug('MultiPermissionMiddleware: User does not have any required permissions - ACCESS DENIED');

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        return redirect()->route('dashboard')->with('error', 'Acceso denegado.');
    }
}
