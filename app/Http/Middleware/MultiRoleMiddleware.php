<?php

namespace App\Http\Middleware;

use App\Repositories\User\UserTypeRepository;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware avanzado para verificar roles múltiples de un usuario.
 * Permite que un usuario tenga varios roles y que se valide si tiene
 * al menos uno de los roles permitidos.
 */
class MultiRoleMiddleware
{
    protected UserTypeRepository $userTypeRepository;

    /**
     * Constructor del middleware.
     *
     * @param UserTypeRepository $userTypeRepository
     */
    public function __construct(UserTypeRepository $userTypeRepository)
    {
        $this->userTypeRepository = $userTypeRepository;
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

        // 1. Verificar autenticación
        if (!$user) {
            return redirect()->route('login')->with('error', 'Por favor, inicie sesión para continuar.');
        }

        // 2. Validar que el tipo de usuario exista y obtener todos sus roles
        $userRoles = $this->userTypeRepository->getRolesByUserId($user->id);

        if (empty($userRoles)) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Usuario inválido o sin roles asignados.');
        }

        // 3. Definir Gates dinámicamente si no existen
        foreach ($roles as $role) {
            if (!Gate::has($role)) {
                Gate::define($role, fn($user) => in_array($role, $userRoles));
            }
        }

        // 4. Verificar si el usuario tiene al menos uno de los roles permitidos
        $hasAccess = false;
        foreach ($roles as $role) {
            if (Gate::allows($role, $user)) {
                $hasAccess = true;
                break;
            }
        }

        // 5. Denegar acceso si no tiene permisos
        if (!$hasAccess) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Acceso denegado.'], 403);
            }
            return redirect()->route('dashboard')->with('error', 'Acceso denegado.');
        }

        return $next($request);
    }
}
