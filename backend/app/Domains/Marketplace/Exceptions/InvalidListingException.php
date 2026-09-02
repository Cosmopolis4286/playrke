<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Exceptions;

/**
 * Lanzada al intentar crear/activar un listing que viola una regla de
 * negocio (activo no tradable, cantidad excede lo disponible, precio
 * inválido, vendedor no es el owner del activo, etc.).
 */
class InvalidListingException extends \DomainException
{
}
