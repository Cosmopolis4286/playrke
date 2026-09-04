<?php

declare(strict_types=1);

namespace App\Domains\Payments\Exceptions;

use App\Exceptions\Domain\DomainException;

/**
 * Lanzada cuando se invoca una transición de PaymentOrder desde un estado
 * de entrada distinto al esperado — mismo rol que InvalidEscrowStateException/
 * InvalidWithdrawalStateException en Marketplace.
 *
 * Antes extendía RuntimeException plano: caía siempre en la rama 3 del
 * pipeline de bootstrap/app.php (500 genérico, mensaje sanitizado en
 * producción) aunque representa una regla de negocio esperada (409), no un
 * fallo real del servidor.
 */
class InvalidPaymentOrderStateException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 409);
    }
}
