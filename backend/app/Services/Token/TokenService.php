<?php

namespace App\Services\Token;

use InvalidArgumentException;

/**
 * Servicio responsable de la generación y validación de tokens.
 *
 * Encapsula la lógica de formato, obligatoriedad y futuras
 * validaciones (persistencia, expiración, scopes, etc.).
 */
class TokenService
{
    /**
     * Longitud esperada del token en caracteres hexadecimales.
     */
    private const TOKEN_LENGTH = 64;

    /**
     * Genera un token criptográficamente seguro.
     *
     * @param int $bytes Número de bytes aleatorios (32 bytes = 64 chars hex).
     * @return string Token generado en formato hexadecimal.
     *
     * @throws \Exception Si random_bytes falla.
     */
    public function generateToken(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    /**
     * Valida un token (obligatorio).
     *
     * El token:
     * - Debe existir
     * - Debe cumplir con el formato esperado
     *
     * @param string|null $token
     * @return string Token validado.
     *
     * @throws InvalidArgumentException Si el token es inválido.
     */
    public function validateToken(?string $token): string
    {
        if (empty($token)) {
            throw new InvalidArgumentException('Token no proporcionado.');
        }

        if (!$this->isValidFormat($token)) {
            throw new InvalidArgumentException('Formato de token inválido.');
        }

        return $token;
    }

    /**
     * Valida un token opcional.
     *
     * Si el token es proporcionado, valida su formato.
     * Si no se proporciona, retorna null sin lanzar excepción.
     *
     * @param string|null $token
     * @return string|null Token validado o null si no se proporcionó.
     *
     * @throws InvalidArgumentException Si el token tiene formato inválido.
     */
    public function validateTokenOptional(?string $token): ?string
    {
        if (empty($token)) {
            return null;
        }

        if (!$this->isValidFormat($token)) {
            throw new InvalidArgumentException('Formato de token inválido.');
        }

        return $token;
    }

    /**
     * Verifica si el token cumple el formato esperado.
     *
     * @param string $token
     * @return bool
     */
    protected function isValidFormat(string $token): bool
    {
        return preg_match(
            sprintf('/^[a-f0-9]{%d}$/', self::TOKEN_LENGTH),
            $token
        ) === 1;
    }
}
