<?php

namespace App\Services;

use App\Contracts\WalletMovementRepositoryInterface;
use App\Contracts\WalletRepositoryInterface;
use App\Events\Wallet\WalletUpdated;
use App\Events\Wallet\WalletMovementRequested;
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

        $movement = $this->movementRepository->record([
            'wallet_id'      => (string) $wallet->id,
            'user_id'        => (string) $wallet->user_id,
            'type'           => WalletMovement::TYPE_DEPOSIT,
            'status'         => WalletMovement::STATUS_PENDING,
            'amount'         => $amount,
            'balance_before' => (float) $wallet->balance,
            'balance_after'  => (float) $wallet->balance,
            'description'    => $notes ?? 'Depósito pendiente de confirmación',
            'reference_id'   => $reference,
            'receipt_path'   => $receipt,
        ]);

        $this->broadcastAdminWalletMovement($movement, $wallet, WalletMovement::TYPE_DEPOSIT, $amount);

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
        $withdrawalMovement = null;

        $wallet = DB::transaction(function () use ($walletId, $amount, $directoryId, $directoryType, $notes, &$withdrawalMovement) {
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

            $withdrawalMovement = $this->movementRepository->record([
                'wallet_id'      => (string) $wallet->id,
                'user_id'        => (string) $wallet->user_id,
                'type'           => WalletMovement::TYPE_WITHDRAWAL,
                'status'         => WalletMovement::STATUS_PENDING,
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => (float) $wallet->balance,
                'description'    => $notes ?? 'Retiro pendiente de procesamiento',
                'metadata'       => [
                    'directory_id'   => $directoryId,
                    'directory_type' => $directoryType,
                ],
            ]);

            return $wallet;
        });

        $this->broadcastWalletUpdated($wallet, WalletMovement::TYPE_WITHDRAWAL, $amount);
        $this->broadcastAdminWalletMovement($withdrawalMovement, $wallet, WalletMovement::TYPE_WITHDRAWAL, $amount);

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
     * Aprobar un depósito PENDING: acredita el monto al saldo y marca COMPLETED.
     */
    public function approveDeposit(WalletMovement $movement, ?string $adminNotes = null): WalletMovement
    {
        if ($movement->type !== WalletMovement::TYPE_DEPOSIT) {
            throw new \DomainException('El movimiento no es un depósito.');
        }
        if ($movement->status !== WalletMovement::STATUS_PENDING) {
            throw new \DomainException('Solo se pueden aprobar movimientos en estado PENDING.');
        }

        $updated = DB::transaction(function () use ($movement, $adminNotes) {
            $this->walletRepository->incrementBalance((string) $movement->wallet_id, (float) $movement->amount);
            $movement->wallet->refresh();

            $movement->update([
                'status'        => WalletMovement::STATUS_COMPLETED,
                'description'   => 'Depósito acreditado',
                'balance_after' => (float) $movement->wallet->balance,
                'admin_notes'   => $adminNotes,
                'processed_by'  => (string) Auth::id(),
                'processed_at'  => now(),
            ]);

            return $movement->fresh(['wallet', 'wallet.currency']);
        });

        $this->broadcastAndNotifyResult($updated, WalletMovement::STATUS_COMPLETED, $adminNotes);

        return $updated;
    }

    /**
     * Rechazar un depósito PENDING: no toca el saldo, marca REJECTED.
     */
    public function rejectDeposit(WalletMovement $movement, ?string $adminNotes = null): WalletMovement
    {
        if ($movement->type !== WalletMovement::TYPE_DEPOSIT) {
            throw new \DomainException('El movimiento no es un depósito.');
        }
        if ($movement->status !== WalletMovement::STATUS_PENDING) {
            throw new \DomainException('Solo se pueden rechazar movimientos en estado PENDING.');
        }

        $movement->update([
            'status'       => WalletMovement::STATUS_REJECTED,
            'description'  => 'Depósito rechazado',
            'admin_notes'  => $adminNotes,
            'processed_by' => (string) Auth::id(),
            'processed_at' => now(),
        ]);

        $updated = $movement->fresh(['wallet', 'wallet.currency']);

        $this->broadcastAndNotifyResult($updated, WalletMovement::STATUS_REJECTED, $adminNotes);

        return $updated;
    }

    /**
     * Aprobar un retiro PENDING: el saldo ya fue descontado, solo marca COMPLETED.
     */
    public function approveWithdrawal(WalletMovement $movement, ?string $adminNotes = null): WalletMovement
    {
        if ($movement->type !== WalletMovement::TYPE_WITHDRAWAL) {
            throw new \DomainException('El movimiento no es un retiro.');
        }
        if ($movement->status !== WalletMovement::STATUS_PENDING) {
            throw new \DomainException('Solo se pueden aprobar movimientos en estado PENDING.');
        }

        $movement->update([
            'status'       => WalletMovement::STATUS_COMPLETED,
            'description'  => 'Retiro procesado',
            'admin_notes'  => $adminNotes,
            'processed_by' => (string) Auth::id(),
            'processed_at' => now(),
        ]);

        $updated = $movement->fresh(['wallet', 'wallet.currency']);

        $this->broadcastAndNotifyResult($updated, WalletMovement::STATUS_COMPLETED, $adminNotes);

        return $updated;
    }

    /**
     * Rechazar un retiro PENDING: devuelve el monto al saldo y marca REJECTED.
     */
    public function rejectWithdrawal(WalletMovement $movement, ?string $adminNotes = null): WalletMovement
    {
        if ($movement->type !== WalletMovement::TYPE_WITHDRAWAL) {
            throw new \DomainException('El movimiento no es un retiro.');
        }
        if ($movement->status !== WalletMovement::STATUS_PENDING) {
            throw new \DomainException('Solo se pueden rechazar movimientos en estado PENDING.');
        }

        $updated = DB::transaction(function () use ($movement, $adminNotes) {
            $this->walletRepository->incrementBalance((string) $movement->wallet_id, (float) $movement->amount);
            $movement->wallet->refresh();

            $movement->update([
                'status'       => WalletMovement::STATUS_REJECTED,
                'description'  => 'Retiro rechazado — saldo reintegrado',
                'admin_notes'  => $adminNotes,
                'processed_by' => (string) Auth::id(),
                'processed_at' => now(),
            ]);

            return $movement->fresh(['wallet', 'wallet.currency']);
        });

        $this->broadcastAndNotifyResult($updated, WalletMovement::STATUS_REJECTED, $adminNotes);

        return $updated;
    }

    /**
     * Broadcast WebSocket + notificación persistente al usuario tras aprobar/rechazar.
     */
    private function broadcastAndNotifyResult(WalletMovement $movement, string $status, ?string $adminNotes): void
    {
        $wallet        = $movement->wallet;
        $currencySymbol = $wallet?->currency?->symbol ?? '';
        $userId        = (string) $movement->user_id;
        $amount        = (float) $movement->amount;

        // WebSocket: notifica al canal privado del usuario
        broadcast(new WalletUpdated(
            userId:         $userId,
            walletId:       (string) $movement->wallet_id,
            balance:        (float) ($wallet?->balance ?? 0),
            currencySymbol: $currencySymbol,
            movementType:   $movement->type,
            amount:         $amount,
            movementStatus: $status,
            movementId:     (string) $movement->id,
        ));

        // Notificación persistente en tabla notifications
        if ($movement->type === WalletMovement::TYPE_DEPOSIT) {
            $this->notificationService->createDepositResultNotification(
                userId:         $userId,
                status:         $status,
                amount:         $amount,
                currencySymbol: $currencySymbol,
                adminNotes:     $adminNotes,
            );
        } else {
            $this->notificationService->createWithdrawalResultNotification(
                userId:         $userId,
                status:         $status,
                amount:         $amount,
                currencySymbol: $currencySymbol,
                adminNotes:     $adminNotes,
            );
        }
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
     * Notifica a admins/superadmins vía WebSocket + DB cuando llega un movimiento pendiente.
     */
    private function broadcastAdminWalletMovement(?WalletMovement $movement, Wallet $wallet, string $type, float $amount): void
    {
        $user     = $wallet->user ?? \App\Models\User::find($wallet->user_id);
        $currency = $wallet->currency;

        $movementId = $movement ? (string) $movement->id : '';
        $userName   = $user
            ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->username ?? $user->email)
            : 'Usuario';

        // 1. Broadcast WebSocket al canal de admins
        broadcast(new WalletMovementRequested(
            movementId:     $movementId,
            userId:         (string) $wallet->user_id,
            userName:       $userName,
            movementType:   $type,
            amount:         $amount,
            currencySymbol: $currency?->symbol ?? '',
            currencyIso:    $currency?->iso_code ?? '',
        ));

        // 2. Notificación persistente en DB para cada admin/superadmin
        $admins = \App\Models\User::withRole('admin')
            ->orWhereHas('roles', fn($q) => $q->where('name', 'superadmin'))
            ->get();

        $typeLabel = $type === WalletMovement::TYPE_DEPOSIT ? 'depósito' : 'retiro';
        $symbol    = $currency?->symbol ?? '';

        foreach ($admins as $admin) {
            $this->notificationService->create(
                userId:  (string) $admin->id,
                type:    'WALLET_ADMIN_ALERT',
                title:   $type === WalletMovement::TYPE_DEPOSIT
                            ? '💰 Nuevo depósito pendiente'
                            : '💸 Nuevo retiro pendiente',
                message: "{$userName} solicitó un {$typeLabel} de {$symbol}{$amount}. Requiere tu aprobación.",
                data:    [
                    'movement_id'   => $movementId,
                    'movement_type' => $type,
                    'amount'        => $amount,
                    'currency_symbol' => $symbol,
                    'user_id'       => (string) $wallet->user_id,
                    'user_name'     => $userName,
                    'url'           => '/wallet-movements',
                ],
            );
        }
    }

    /**
     * Obtiene una wallet del usuario autenticado o lanza 403.
     */
    private function findOwnedWallet(string $walletId): Wallet
    {
        $wallet = Wallet::with(['currency', 'user'])->findOrFail($walletId);

        if ((string) $wallet->user_id !== (string) Auth::id()) {
            abort(403, 'No autorizado.');
        }

        return $wallet;
    }
}
