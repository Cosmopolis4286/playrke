<?php

namespace App\Services;

use App\Contracts\WalletRepositoryInterface;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class WalletService
{
    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository,
    ) {}

    /**
     * Wallets activas del usuario autenticado con moneda cargada.
     */
    public function getPlayerWallets(): array
    {
        $userId = (string) Auth::id();

        return $this->walletRepository
            ->getWalletsByUser($userId, ['status' => 'ACTIVE'])
            ->map(fn(Wallet $w) => [
                'id'           => (string) $w->id,
                'balance'      => (float) $w->balance,
                'status'       => $w->status,
                'currency_id'  => (string) $w->currency_id,
                'currency'     => [
                    'iso_code'       => $w->currency->iso_code,
                    'symbol'         => $w->currency->symbol,
                    'name'           => $w->currency->name,
                    'decimal_places' => $w->currency->decimal_places ?? 2,
                ],
            ])
            ->values()
            ->toArray();
    }

    /**
     * Registrar solicitud de depósito.
     * Por ahora incrementa el balance directamente (mock).
     * Adaptar cuando haya integración de pagos real.
     */
    public function deposit(string $walletId, float $amount): Wallet
    {
        return DB::transaction(function () use ($walletId, $amount) {
            $wallet = $this->findOwnedWallet($walletId);

            if ($wallet->isBlocked()) {
                throw new \DomainException('La billetera está bloqueada.');
            }

            $this->walletRepository->incrementBalance($walletId, $amount);
            $wallet->refresh();

            return $wallet;
        });
    }

    /**
     * Registrar solicitud de retiro.
     */
    public function withdraw(string $walletId, float $amount): Wallet
    {
        return DB::transaction(function () use ($walletId, $amount) {
            $wallet = $this->findOwnedWallet($walletId);

            if ($wallet->isBlocked()) {
                throw new \DomainException('La billetera está bloqueada.');
            }

            if (! $wallet->hasSufficientBalance($amount)) {
                throw new \DomainException('Saldo insuficiente para realizar el retiro.');
            }

            $this->walletRepository->decrementBalance($walletId, $amount);
            $wallet->refresh();

            return $wallet;
        });
    }

    /**
     * Obtiene una wallet del usuario autenticado o lanza 403.
     */
    private function findOwnedWallet(string $walletId): Wallet
    {
        $wallet = Wallet::with('currency')->findOrFail($walletId);

        if ((string) $wallet->user_id !== (string) Auth::id()) {
            abort(403, 'No autorizado.');
        }

        return $wallet;
    }
}
