<?php

namespace App\Http\Middleware;

use App\Repositories\Users\Roles\RoleRepository;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para validar roles de usuario de manera segura.
 * Combina la verificación de existencia del tipo de usuario en BD con Gates/Policies.
 */
class RoleMiddleware
{
    protected RoleRepository $roleRepository;

    /**
     * Constructor del middleware.
     *
     * @param RoleRepository $roleRepository
     */
    public function __construct(RoleRepository $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }

    /**
     * Maneja la petición entrante.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param mixed ...$roles Lista de roles permitidos
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, mixed ...$roles): Response
    {
        $user = Auth::user();

        // Verificar si el usuario está autenticado
        if (!$user) {
            return redirect()->route('login')->with('error', 'Por favor, inicie sesión para continuar.');
        }

        // Validar que el tipo de usuario exista
        $userRolePrefix = $this->roleRepository->getValueById('ff', 'prefix');
        if (!$userRolePrefix) {
            Auth::logout(); // Cierra sesión si tipo inválido
            return redirect()->route('login')->with('error', 'Usuario inválido.');
        }

        // Definir Gates dinámicamente si no existen aún
        foreach ($roles as $role) {
            if (!Gate::has($role)) {
                Gate::define($role, fn($user) => $this->roleRepository->getValueById($user->user_type, 'prefix') === $role);
            }
        }

        // Verificar si el usuario tiene alguno de los roles permitidos
        $hasAccess = false;
        foreach ($roles as $role) {
            if (Gate::allows($role, $user)) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Acceso denegado.'], 403);
            }
            return redirect()->route('dashboard')->with('error', 'Acceso denegado.');
        }

        return $next($request);
    }
}
