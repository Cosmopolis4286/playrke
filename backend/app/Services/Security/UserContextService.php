<?php

namespace App\Services\Security;

use App\Exceptions\Domain\DomainException as AppDomainException;
use DomainException;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio encargado de resolver el contexto del usuario que
 * ejecuta una acción dentro del sistema.
 *
 * Prioridad de resolución:
 *  1. Usuario autenticado vía Auth (session / guard).
 *  2. Token público proporcionado explícitamente.
 */
final class UserContextService
{
    public function __construct(
        private readonly TokenUserResolver $tokenResolver,
        private readonly \App\Services\Roles\RoleUserService $roleUserService,
    ) {
    }

    /**
     * Indica si existe un usuario autenticado en el contexto actual.
     *
     * @return bool true si el usuario está autenticado, false en caso contrario
     */
    public function isAuthenticated() : bool
    {
        return Auth::check();
    }

    /**
     * Resuelve el identificador del usuario que ejecuta la acción.
     *
     * - Si existe un usuario autenticado, se utiliza el ID del Auth guard.
     * - En caso contrario, se intenta resolver el usuario a partir de un token público.
     *
     * @param string|null $publicToken token público opcional para identificar al usuario
     *
     * @return int ID del usuario resuelto
     *
     * @throws DomainException si no existe un usuario autenticado
     *                         y no se proporciona un token válido
     */
    public function resolveUserId(?string $publicToken = null) : int
    {
        if (Auth::check()) {
            return (int) Auth::id();
        }

        if ($publicToken) {
            return $this->tokenResolver->resolve($publicToken);
        }

        throw new DomainException('Usuario no identificado.');
    }

    /**
     * Verifica si el usuario autenticado tiene un rol específico.
     *
     * @param string $roleName Nombre del rol a verificar (ej. 'player', 'admin', 'seller')
     *
     * @return bool true si el usuario tiene el rol, false en caso contrario
     */
    public function userHasRole(string $roleName) : bool
    {
        if (! $this->isAuthenticated()) {
            return false;
        }

        try {
            $rolePrefix = $this->roleUserService->getActiveUserRolePrefix((int) Auth::id());

            return strtolower($rolePrefix) === strtolower($roleName);
        } catch (AppDomainException $e) {
            return false;
        }
    }
}
