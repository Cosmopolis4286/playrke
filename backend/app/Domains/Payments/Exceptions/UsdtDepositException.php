<?php

declare(strict_types=1);

namespace App\Domains\Payments\Exceptions;

use DomainException;

/** Error de negocio del depósito USDT, con el status HTTP que corresponde. */
final class UsdtDepositException extends DomainException
{
    public static function disabled() : self
    {
        return new self('Los depósitos con USDT no están disponibles por ahora.', 404);
    }

    public static function invalidAmount() : self
    {
        return new self('El monto debe ser un número positivo con hasta 2 decimales.');
    }

    public static function invalidTxHash() : self
    {
        return new self('El código de transacción (txid) no es válido: son 64 caracteres hexadecimales.');
    }

    public static function notClaimable() : self
    {
        return new self('Esta orden ya no admite reportar un pago.', 409);
    }

    public static function notInReview() : self
    {
        return new self('La orden no está en revisión.', 409);
    }

    public static function noUniqueAmountAvailable() : self
    {
        return new self('No pudimos generar el monto de pago. Intenta de nuevo en un momento.', 503);
    }

    public static function selfReview() : self
    {
        return new self('No puedes revisar un depósito propio.', 403);
    }

    public static function txHashAlreadyUsed() : self
    {
        return new self('Ese txid ya fue reportado o acreditado en otra orden.', 409);
    }

    public function __construct(string $message, private readonly int $httpStatus = 422)
    {
        parent::__construct($message);
    }

    public function httpStatus() : int
    {
        return $this->httpStatus;
    }
}
