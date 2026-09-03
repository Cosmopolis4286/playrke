<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Crypto\DTOs;

/**
 * Shape de una transacción tal como la reporta la fuente externa (nodo/
 * indexador de blockchain, o el proveedor de custodia). DepositWatcher no
 * confía en ningún campo de este DTO sin validarlo contra la whitelist de
 * config('crypto.tokens') — ver MARKETPLACE_ARCHITECTURE_AUDIT.md sección 23:
 * "nunca inferir el token del formato de la dirección o del monto".
 */
final class RawCryptoTransaction
{
    public function __construct(
        public readonly string $txHash,
        public readonly string $network,
        public readonly string $token,
        public readonly string $fromAddress,
        public readonly string $toAddress,
        public readonly float $amount,
        public readonly int $confirmations,
    ) {
    }
}
