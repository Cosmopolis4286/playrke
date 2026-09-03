<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Crypto\Exceptions;

/**
 * Lanzada por cualquier operación de CryptoGateway que requiera custodia
 * real (firmar, transmitir, consultar confirmaciones on-chain reales)
 * mientras solo existe la implementación STUB de la Fase 5. Ver
 * MARKETPLACE_ARCHITECTURE_AUDIT.md sección 12 — bloqueado hasta decidir
 * proveedor de custodia y resolver la validación regulatoria.
 */
class CryptoCustodyNotConfiguredException extends \DomainException
{
}
