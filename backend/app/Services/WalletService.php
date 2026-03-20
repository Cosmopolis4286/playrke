<?php

namespace App\Services;

use App\Contracts\WalletMovementRepositoryInterface;
use App\Contracts\WalletRepositoryInterface;
use App\Events\Wallet\WalletUpdated;
use App\Models\Wallet;
use App\Models\WalletMovement;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class WalletService
{
    public function __construct(
        private readonly WalletRepositoryInterface         $walletRepository,
        private readonly WalletMovementRepositoryInterface $movementRepository,
        private readonly NotificationService                $notificationService,
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
        $wallet = DB::transaction(function () use ($walletId, $amount) {
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

            return $wallet;
        });

        // Broadcast FUERA de la transacción: garantiza que el commit ya ocurrió
        $this->broadcastWalletUpdated($wallet, WalletMovement::TYPE_DEPOSIT, $amount);

        return $wallet;
    }

    /**
     * Registrar solicitud de retiro.
     */
    public function withdraw(string $walletId, float $amount): Wallet
    {
        $wallet = DB::transaction(function () use ($walletId, $amount) {
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

            return $wallet;
        });

        // Broadcast FUERA de la transacción: garantiza que el commit ya ocurrió
        $this->broadcastWalletUpdated($wallet, WalletMovement::TYPE_WITHDRAWAL, $amount);

        return $wallet;
    }

    /**
     * Registrar una compra de cartones en la billetera.
     *
     * @param string $walletId ID de la billetera
     * @param float $amount Monto de la compra
     * @param string $description Descripción de la compra (opcional)
     * @return Wallet
     * @throws \DomainException Si no hay saldo suficiente
     */
    public function recordCardPurchase(string $walletId, float $amount, string $description = 'Compra de cartones'): Wallet
    {
        $wallet = DB::transaction(function () use ($walletId, $amount, $description) {
            $wallet = $this->findOwnedWallet($walletId);

            if (! $wallet->hasSufficientBalance($amount)) {
                throw new \DomainException('Saldo insuficiente para realizar la compra de cartones.');
            }

            $balanceBefore = (float) $wallet->balance;

            $this->walletRepository->decrementBalance($walletId, $amount);
            $wallet->refresh();

            $this->movementRepository->record([
                'wallet_id'      => (string) $wallet->id,
                'user_id'        => (string) $wallet->user_id,
                'type'           => WalletMovement::TYPE_PURCHASE,
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => (float) $wallet->balance,
                'description'    => $description,
            ]);

            return $wallet;
        });

        // Broadcast FUERA de la transacción: garantiza que el commit ya ocurrió
        $this->broadcastWalletUpdated($wallet, WalletMovement::TYPE_PURCHASE, $amount);

        return $wallet;
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
        // Emitir evento WebSocket para actualización en tiempo real
        broadcast(new WalletUpdated(
            userId:         (string) $wallet->user_id,
            walletId:       (string) $wallet->id,
            balance:        (float)  $wallet->balance,
            currencySymbol: $wallet->currency->symbol ?? '',
            movementType:   $type,
            amount:         $amount,
        ));

        // Guardar notificación persistente
        $this->notificationService->createWalletNotification(
            userId: (string) $wallet->user_id,
            balance: (float) $wallet->balance,
            currencySymbol: $wallet->currency->symbol ?? '',
            movementType: $type,
            amount: $amount
        );
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
