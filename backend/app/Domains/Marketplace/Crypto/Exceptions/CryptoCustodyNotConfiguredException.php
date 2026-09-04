<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Crypto\Exceptions;

use App\Exceptions\Domain\DomainException;

/**
 * Lanzada por cualquier operación de CryptoGateway que requiera custodia
 * real (firmar, transmitir, consultar confirmaciones on-chain reales)
 * mientras solo existe la implementación STUB de la Fase 5. Ver
 * MARKETPLACE_ARCHITECTURE_AUDIT.md sección 12 — bloqueado hasta decidir
 * proveedor de custodia y resolver la validación regulatoria.
 *
 * Antes extendía \DomainException nativo y caía en 500 genérico en
 * producción (ver docblock de InvalidListingException para el porqué
 * exacto).
 */
class CryptoCustodyNotConfiguredException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 422);
    }
}
