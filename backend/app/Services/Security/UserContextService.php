<?php

namespace App\Services\Security;

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
    ) {}

    /**
     * Resuelve el identificador del usuario que ejecuta la acción.
     *
     * - Si existe un usuario autenticado, se utiliza el ID del Auth guard.
     * - En caso contrario, se intenta resolver el usuario a partir de un token público.
     *
     * @param string|null $publicToken Token público opcional para identificar al usuario.
     *
     * @return int ID del usuario resuelto.
     *
     * @throws DomainException Si no existe un usuario autenticado
     *                         y no se proporciona un token válido.
     */
    public function resolveUserId(?string $publicToken = null): int
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
     * Indica si existe un usuario autenticado en el contexto actual.
     *
     * @return bool True si el usuario está autenticado, false en caso contrario.
     */
    public function isAuthenticated(): bool
    {
        return Auth::check();
    }
}
