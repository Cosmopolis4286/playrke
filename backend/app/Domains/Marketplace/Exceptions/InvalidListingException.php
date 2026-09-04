<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Exceptions;

use App\Exceptions\Domain\DomainException;

/**
 * Lanzada al intentar crear/activar un listing que viola una regla de
 * negocio (activo no tradable, cantidad excede lo disponible, precio
 * inválido, vendedor no es el owner del activo, etc.).
 *
 * Antes extendía \DomainException nativo de PHP: bootstrap/app.php filtra
 * su rama 2 con `get_class($e) !== \DomainException::class` (comparación
 * exacta, no instanceof, a propósito para no capturar excepciones de
 * terceros) — una subclase nunca cumple esa igualdad, así que esta
 * excepción caía silenciosamente en la rama 3 (500 genérico, mensaje
 * sanitizado en producción) en vez de los 422 que sus 7 call sites en
 * ListingService/OrderService esperan.
 */
class InvalidListingException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 422);
    }
}
