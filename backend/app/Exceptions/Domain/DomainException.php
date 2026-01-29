<?php

namespace App\Exceptions\Domain;

use RuntimeException;
use Throwable;

/**
 * Excepción base para errores de dominio.
 *
 * El message representa un ERROR CODE, no un texto para el usuario.
 */
abstract class DomainException extends RuntimeException
{
    /**
     * Código de error de dominio.
     */
    protected string $errorCode;

    public function __construct(
        string $errorCode,
        int $httpCode = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($errorCode, $httpCode, $previous);

        $this->errorCode = $errorCode;
    }

    /**
     * Obtener el error code de dominio.
     */
    public function errorCode(): string
    {
        return $this->errorCode;
    }
}
