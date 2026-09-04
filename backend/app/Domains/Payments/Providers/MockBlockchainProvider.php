<?php

declare(strict_types=1);

namespace App\Domains\Payments\Providers;

use App\Domains\Payments\Contracts\BlockchainProviderInterface;
use App\Domains\Payments\Exceptions\PaymentsProviderMisconfiguredException;

/**
 * Implementación en memoria de BlockchainProviderInterface para tests y
 * desarrollo local — nunca hace ninguna llamada de red real. Ver
 * PAYMENTS_USDT_TRC20_DESIGN.md sección 7.
 *
 * PaymentServiceProvider rehúsa bindear esta clase si
 * app()->environment('production') — un pago real jamás puede confirmarse
 * contra este mock.
 *
 * Los tests inyectan transacciones vía pushTransaction()/setConfirmations()
 * antes de ejercitar BlockchainWatcherService, simulando lo que el
 * proveedor real reportaría.
 */
class MockBlockchainProvider implements BlockchainProviderInterface
{
    /** @var array<string, \App\Domains\Payments\DTOs\InboundTransaction[]> */
    private array $transactionsByAddress = [];

    /** @var array<string, int> */
    private array $confirmationsByTxHash = [];

    public function __construct()
    {
        if (app()->environment('production')) {
            throw new PaymentsProviderMisconfiguredException(
                'MockBlockchainProvider no puede usarse en producción.'
            );
        }
    }

    public function network(): string
    {
        return 'TRON';
    }

    public function deriveAddress(int $derivationIndex): string
    {
        return 'TMOCK' . str_pad((string) $derivationIndex, 30, '0', STR_PAD_LEFT);
    }

    public function getIncomingTransactions(string $address, ?int $sinceBlock = null): array
    {
        $transactions = $this->transactionsByAddress[$address] ?? [];

        if ($sinceBlock === null) {
            return $transactions;
        }

        return array_values(array_filter(
            $transactions,
            fn ($tx) => $tx->blockNumber >= $sinceBlock,
        ));
    }

    public function getConfirmations(string $txHash): ?int
    {
        return $this->confirmationsByTxHash[$txHash] ?? null;
    }

    public function tokenContract(): string
    {
        return config('payments.tron.usdt_contract');
    }

    public function pushTransaction(\App\Domains\Payments\DTOs\InboundTransaction $transaction): void
    {
        $this->transactionsByAddress[$transaction->toAddress][] = $transaction;
        $this->confirmationsByTxHash[$transaction->txHash] = $transaction->confirmations;
    }

    public function setConfirmations(string $txHash, int $confirmations): void
    {
        $this->confirmationsByTxHash[$txHash] = $confirmations;
    }
}
