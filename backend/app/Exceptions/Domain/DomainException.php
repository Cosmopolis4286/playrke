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

    /**
     * Placeholders para trans() cuando $errorCode es un ERROR_CODE MAYUSC
     * traducible con datos dinámicos (ej. 'BINGO_ROOM_MIN_CARDS_NOT_REACHED'
     * con ['required' => 50, 'current' => 12], traducido a "La sala
     * requiere al menos :required cartones..." vía lang/{locale}/errors.php).
     * Vacío por defecto — la mayoría de error-codes no llevan datos
     * dinámicos, y los mensajes humanos directos (sin traducción) los
     * llevan ya interpolados en el propio string.
     *
     * @var array<string, mixed>
     */
    protected array $replacements;

    public function __construct(
        string $errorCode,
        int $httpStatus = 422,
        ?Throwable $previous = null,
        array $replacements = [],
    ) {
        parent::__construct($errorCode, 0, $previous);

        $this->errorCode = $errorCode;
        $this->httpStatus = $httpStatus;
        $this->replacements = $replacements;
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

    /**
     * Placeholders para resolver la traducción de errorCode() vía trans().
     *
     * @return array<string, mixed>
     */
    public function replacements(): array
    {
        return $this->replacements;
    }
}
