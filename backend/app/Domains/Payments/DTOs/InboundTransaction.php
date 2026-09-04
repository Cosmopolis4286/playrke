<?php

declare(strict_types=1);

namespace App\Domains\Payments\DTOs;

/**
 * Transacción entrante ya observada en la blockchain, devuelta por
 * BlockchainProviderInterface::getIncomingTransactions(). Estructura
 * agnóstica de proveedor — ni BlockchainWatcherService ni PaymentOrderService
 * conocen el formato nativo de TronGrid ni de ningún otro indexador.
 */
final readonly class InboundTransaction
{
    public function __construct(
        public string $txHash,
        public string $fromAddress,
        public string $toAddress,
        public string $tokenContract,
        public float $amount,
        public int $blockNumber,
        public int $timestamp,
        public int $confirmations,
    ) {
    }
}
