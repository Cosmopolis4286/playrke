<?php

declare(strict_types=1);

namespace App\Domains\Payments\Exceptions;

use App\Exceptions\Domain\DomainException;

/**
 * Lanzada al intentar crear una PaymentOrder para un producto/pack
 * inactivo o inexistente.
 *
 * Antes extendía RuntimeException plano: caía siempre en la rama 3 del
 * pipeline de bootstrap/app.php (500 genérico, mensaje sanitizado en
 * producción) aunque es una regla de negocio esperada (422), no un fallo
 * real del servidor.
 */
class PaymentProductNotAvailableException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 422);
    }
}
