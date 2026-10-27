<?php

namespace App\Services\Security;

use DomainException;

/**
 * Servicio de dominio encargado de resolver la identidad del usuario
 * a partir de un token de autenticación.
 *
 * Este resolver puede adaptarse a múltiples estrategias:
 * - API Keys
 * - JWT
 * - Tokens persistidos en DB
 * - Redis / Cache
 */
final class TokenUserResolver
{
    /**
     * Resuelve el identificador del usuario a partir de un token.
     *
     * En esta implementación básica, el token debe ser un valor numérico
     * representando directamente el ID del usuario.
     *
     * @param string $token token de autenticación proporcionado por el cliente
     *
     * @return int ID del usuario resuelto a partir del token
     *
     * @throws DomainException si el token no es válido o no puede resolverse
     */
    public function resolve(string $token) : int
    {
        // Placeholder → API Key / JWT / DB / Redis
        if (! is_numeric($token)) {
            throw new DomainException('Token inválido.');
        }

        return (int) $token;
    }
}
