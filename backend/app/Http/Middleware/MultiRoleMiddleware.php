<?php

namespace App\Http\Middleware;

use App\Repositories\Users\Roles\RoleUserRepository;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class MultiRoleMiddleware
{
    protected RoleUserRepository $roleUserRepository;

    public function __construct(RoleUserRepository $roleUserRepository)
    {
        $this->roleUserRepository = $roleUserRepository;
    }

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Por favor, inicie sesión para continuar.');
        }

        // Obtener roles activos asignados al usuario (con prefijos)
        $activeAssignments = $this->roleUserRepository->getActiveByUser($user->id);

        if ($activeAssignments->isEmpty()) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Usuario inválido o sin roles asignados.');
        }

        // Extraer prefijos de roles activos
        $userRolesPrefixes = $activeAssignments->map(function ($assignment) {
            return $assignment->role->prefix ?? null;
        })->filter()->unique()->toArray();

        // Definir gates dinámicos para roles solicitados
        foreach ($roles as $role) {
            if (!Gate::has($role)) {
                Gate::define($role, fn($user) => in_array($role, $userRolesPrefixes, true));
            }
        }

        // Validar acceso por alguno de los roles permitidos
        foreach ($roles as $role) {
            if (Gate::allows($role, $user)) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        return redirect()->route('dashboard')->with('error', 'Acceso denegado.');
    }
}
