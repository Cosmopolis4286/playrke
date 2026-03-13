<?php

namespace App\Services;

use App\Contracts\WalletMovementRepositoryInterface;
use App\Contracts\WalletRepositoryInterface;
use App\Events\Wallet\WalletUpdated;
use App\Models\Wallet;
use App\Models\WalletMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class WalletService
{
    public function __construct(
        private readonly WalletRepositoryInterface         $walletRepository,
        private readonly WalletMovementRepositoryInterface $movementRepository,
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
     */
    public function deposit(string $walletId, float $amount): Wallet
    {
        return DB::transaction(function () use ($walletId, $amount) {
            $wallet = $this->findOwnedWallet($walletId);

            if ($wallet->isBlocked()) {
                throw new \DomainException('La billetera está bloqueada.');
            }

            $balanceBefore = (float) $wallet->balance;

            $this->walletRepository->incrementBalance($walletId, $amount);
            $wallet->refresh();

            $this->movementRepository->record([
                'wallet_id'      => (string) $wallet->id,
                'user_id'        => (string) $wallet->user_id,
                'type'           => WalletMovement::TYPE_DEPOSIT,
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => (float) $wallet->balance,
                'description'    => 'Depósito manual',
            ]);

            $this->broadcastWalletUpdated($wallet, WalletMovement::TYPE_DEPOSIT, $amount);

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

            $balanceBefore = (float) $wallet->balance;

            $this->walletRepository->decrementBalance($walletId, $amount);
            $wallet->refresh();

            $this->movementRepository->record([
                'wallet_id'      => (string) $wallet->id,
                'user_id'        => (string) $wallet->user_id,
                'type'           => WalletMovement::TYPE_WITHDRAWAL,
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => (float) $wallet->balance,
                'description'    => 'Retiro manual',
            ]);

            $this->broadcastWalletUpdated($wallet, WalletMovement::TYPE_WITHDRAWAL, $amount);

            return $wallet;
        });
    }

    /**
     * Movimientos paginados de una billetera del usuario autenticado.
     */
    public function getMovements(string $walletId, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $this->findOwnedWallet($walletId); // verifica propiedad

        return $this->movementRepository->getByWallet($walletId, $perPage);
    }

    /**
     * Emite el evento WebSocket con el nuevo saldo.
     */
    private function broadcastWalletUpdated(Wallet $wallet, string $type, float $amount): void
    {
        broadcast(new WalletUpdated(
            userId:         (string) $wallet->user_id,
            walletId:       (string) $wallet->id,
            balance:        (float)  $wallet->balance,
            currencySymbol: $wallet->currency->symbol ?? '',
            movementType:   $type,
            amount:         $amount,
        ));
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
