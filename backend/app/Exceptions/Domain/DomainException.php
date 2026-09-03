<?php

namespace App\Exceptions\Domain;

use RuntimeException;
use Throwable;

/**
 * Excepción base para errores de dominio.
 *
 * El message representa un ERROR CODE (ej. 'BINGO_ROOM_NOT_FOUND'), no un
 * texto para el usuario — se resuelve a mensaje humano vía
 * App\Exceptions\Handler::resolveMessage() + lang/{locale}/errors.php.
 *
 * $httpStatus se declara EN CADA factory method (ej.
 * `new self('BINGO_ROOM_NOT_FOUND', 404)`), no en un mapa centralizado:
 * antes, el status vivía únicamente en Handler::resolveStatusCode() (un
 * único match plano de 45 entradas para ~65 códigos usados en 14 clases),
 * que se desincronizó silenciosamente — dominios enteros (BingoRoom,
 * AdminSubsidiary, AdminPlayer...) quedaron sin mapear y caían al 400
 * genérico sin que nada lo detectara. Declararlo junto al código que lo
 * dispara hace que ambos vivan en el mismo lugar: imposible que se
 * desincronicen entre sí.
 */
abstract class DomainException extends RuntimeException
{
    /**
     * Código de error de dominio.
     */
    protected string $errorCode;

    /**
     * Status HTTP real de este error — 422 por defecto (regla de negocio
     * violada), no 0 (que Handler::resolveStatusCode() interpretaba como
     * "sin declarar" y resolvía desde su propio mapa central).
     */
    protected int $httpStatus;

    public function __construct(
        string $errorCode,
        int $httpStatus = 422,
        ?Throwable $previous = null
    ) {
        parent::__construct($errorCode, 0, $previous);

        $this->errorCode = $errorCode;
        $this->httpStatus = $httpStatus;
    }

    /**
     * Obtener el error code de dominio.
     */
    public function errorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Status HTTP declarado por el factory method que creó esta excepción.
     */
    public function httpStatus(): int
    {
        return $this->httpStatus;
    }
}
