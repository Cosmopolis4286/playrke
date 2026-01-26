<?php

namespace App\Services\Token;

use InvalidArgumentException;

class TokenService
{
    /**
     * Genera un token aleatorio seguro.
     *
     * @param int $length Longitud del token generado (por defecto 32).
     * @return string Token generado en formato hexadecimal.
     */
    public function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Valida un token según la lógica definida.
     *
     * @param string|null $token Token a validar.
     * @return void
     *
     * @throws InvalidArgumentException Si el token es inválido o está vacío.
     */
    public function validateToken(?string $token): void
    {
        if ($token && !$this->isValidFormat($token)) {
            throw new InvalidArgumentException('Token inválido o no proporcionado.');
        }
    }

    /**
     * Verifica el formato básico del token.
     *
     * @param string $token
     * @return bool
     */
    protected function isValidFormat(string $token): bool
    {
        // Por ejemplo, solo tokens hexadecimales con longitud 64 (32 bytes * 2).
        return preg_match('/^[a-f0-9]{64}$/', $token) === 1;
    }
}
