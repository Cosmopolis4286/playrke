<?php

namespace App\Services\Roles;

use App\Contracts\Roles\RoleUserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Request;

class RoleUserService
{
    public function __construct(
        protected RoleUserRepositoryInterface $repository,
    ) {}

    /**
     * Resuelve el prefijo del tipo de usuario.
     *
     * @param int $userId
     * @return string|null
     */
    /**
     * Resuelve el prefijo del tipo de usuario basándose en roles activos.
     */
    public function getActiveUserRolePrefix(int $userId): ?string
    {
        // Obtener roles activos asignados al usuario
        $activeRoles = $this->repository->getActiveByUser($userId);

        foreach ($activeRoles as $roleUser) {
            $role = $roleUser->role;
            if ($role && $role->prefix) {
                // Retornamos el primer prefijo encontrado (ajustar lógica si hay más de uno)
                return strtolower($role->prefix);
            }
        }

        return null;
    }

    /**
     * Resuelve el contexto del usuario que realiza la operación.
     *
     * Determina si la solicitud proviene de un usuario autenticado
     * o de un usuario público identificado en la request.
     *
     * Retorna una tupla con:
     * - ID del usuario
     * - Flag indicando si es un usuario autenticado
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array{0:int,1:bool} [
     *     int  $userId,        // Identificador del usuario
     *     bool $isAuthenticated // true si está autenticado
     * ]
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function resolveUser(Request $request): array
    {
        if (Auth::check()) {
            return [Auth::id(), true];
        }

        $userId = $request->attributes->get('public_user');

        if (!$userId) {
            abort(404);
        }

        return [$userId, false];
    }
}
