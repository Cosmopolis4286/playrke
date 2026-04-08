<?php

namespace App\Services;

use App\Contracts\WalletMovementRepositoryInterface;
use App\Contracts\WalletRepositoryInterface;
use App\Events\Wallet\WalletUpdated;
use App\Models\Directories\Crypto\Binance\BinanceAccounts;
use App\Models\Directories\Fiat\Brazil\PixKey;
use App\Models\Directories\Fiat\Venezuela\PagoMovil;
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
     * Verifica si la billetera tiene una solicitud de depósito o retiro en cola (PENDING).
     * Retorna el movimiento pendiente o null.
     */
    public function getPendingOperation(string $walletId, string $type): ?WalletMovement
    {
        return WalletMovement::where('wallet_id', $walletId)
            ->where('type', $type)
            ->where('status', WalletMovement::STATUS_PENDING)
            ->latest()
            ->first();
    }

    /**
     * Directorios de pago del usuario filtrados según la moneda de la billetera.
     * VES → PagoMovil | BRL → PIX | USDT → Binance
     */
    public function getUserDirectories(string $userId, string $walletId): array
    {
        $wallet = Wallet::with('currency')->findOrFail($walletId);

        if ((string) $wallet->user_id !== $userId) {
            throw new \DomainException('No autorizado.');
        }

        $iso = strtoupper($wallet->currency->iso_code);

        return match (true) {
            in_array($iso, ['VES', 'VED', 'BSS']) => $this->getPagoMovilDirectories($userId),
            $iso === 'BRL'                         => $this->getPixDirectories($userId),
            in_array($iso, ['USDT', 'BTC', 'ETH']) => $this->getBinanceDirectories($userId),
            default                                => [],
        };
    }

    private function getPagoMovilDirectories(string $userId): array
    {
        return PagoMovil::where('user_id', $userId)
            ->where('status', 'ACTIVE')
            ->with(['bank', 'documentType'])
            ->get()
            ->map(fn($pm) => [
                'id'      => (string) $pm->id,
                'type'    => 'pago_movil',
                'label'   => $pm->bank->name ?? 'Banco',
                'detail'  => ($pm->documentType->code ?? 'CI') . '-' . $pm->document_number . ' | ' . $pm->phone,
                'holder'  => $pm->holder_name,
            ])
            ->values()
            ->toArray();
    }

    private function getPixDirectories(string $userId): array
    {
        return PixKey::where('user_id', $userId)
            ->where('status', 'ACTIVE')
            ->with('bank')
            ->get()
            ->map(fn($pix) => [
                'id'      => (string) $pix->id,
                'type'    => 'pix',
                'label'   => $pix->bank->name ?? 'Banco',
                'detail'  => "{$pix->getTypeDisplayName()}: {$pix->formatted_key}",
                'holder'  => $pix->holder_name,
            ])
            ->values()
            ->toArray();
    }

    private function getBinanceDirectories(string $userId): array
    {
        return BinanceAccounts::where('user_id', $userId)
            ->where('status', 'ACTIVE')
            ->get()
            ->map(fn($b) => [
                'id'      => (string) $b->id,
                'type'    => 'binance',
                'label'   => $b->account_type_description,
                'detail'  => $b->data_value,
                'holder'  => null,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Registrar solicitud de depósito con comprobante (STATUS_PENDING, sin tocar el saldo).
     */
    public function requestDeposit(
        string $walletId,
        float  $amount,
        string $receipt,
        ?string $reference = null,
        ?string $notes = null,
    ): Wallet {
        $wallet = $this->findOwnedWallet($walletId);

        if ($wallet->isBlocked()) {
            throw new \DomainException('La billetera está bloqueada.');
        }

        if ($this->getPendingOperation($walletId, WalletMovement::TYPE_DEPOSIT)) {
            throw new \DomainException('Ya tienes un depósito pendiente de confirmación. Espera a que sea procesado antes de hacer otro.');
        }

        $this->movementRepository->record([
            'wallet_id'      => (string) $wallet->id,
            'user_id'        => (string) $wallet->user_id,
            'type'           => WalletMovement::TYPE_DEPOSIT,
            'status'         => WalletMovement::STATUS_PENDING,
            'amount'         => $amount,
            'balance_before' => (float) $wallet->balance,
            'balance_after'  => (float) $wallet->balance,
            'description'    => $notes ?? 'Depósito pendiente de confirmación',
            'reference_id'   => $reference ?? $receipt,
        ]);

        return $wallet;
    }

    /**
     * Registrar solicitud de retiro indicando directorio destino (STATUS_PENDING, sin tocar el saldo).
     */
    /**
     * Solicitud de retiro: descuenta el saldo inmediatamente (PENDING).
     * Si admin rechaza/cancela, debe devolverse el monto manualmente.
     */
    public function requestWithdraw(
        string $walletId,
        float  $amount,
        string $directoryId,
        string $directoryType,
        ?string $notes = null,
    ): Wallet {
        $wallet = DB::transaction(function () use ($walletId, $amount, $directoryId, $directoryType, $notes) {
            $wallet = $this->findOwnedWallet($walletId);

            if ($wallet->isBlocked()) {
                throw new \DomainException('La billetera está bloqueada.');
            }

            if ($this->getPendingOperation($walletId, WalletMovement::TYPE_WITHDRAWAL)) {
                throw new \DomainException('Ya tienes un retiro pendiente de procesamiento. Espera a que sea procesado antes de solicitar otro.');
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
                'status'         => WalletMovement::STATUS_PENDING,
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => (float) $wallet->balance,
                'description'    => $notes ?? 'Retiro pendiente de procesamiento',
                'reference_id'   => "{$directoryType}:{$directoryId}",
            ]);

            return $wallet;
        });

        $this->broadcastWalletUpdated($wallet, WalletMovement::TYPE_WITHDRAWAL, $amount);

        return $wallet;
    }

    /**
     * Registrar solicitud de depósito.
     * @deprecated Usar requestDeposit()
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
                'status'         => WalletMovement::STATUS_PENDING,
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
                'status'         => WalletMovement::STATUS_PENDING,
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
                'status'         => WalletMovement::STATUS_COMPLETED,
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
