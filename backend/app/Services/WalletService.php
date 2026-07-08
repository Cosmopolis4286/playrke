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
use App\Services\WalletFraudService;
use App\Services\WithdrawalLimitService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class WalletService
{
    public function __construct(
        private readonly WalletRepositoryInterface         $walletRepository,
        private readonly WalletMovementRepositoryInterface $movementRepository,
        private readonly NotificationService                $notificationService,
        private readonly WithdrawalLimitService             $withdrawalLimitService,
        private readonly WalletFraudService                 $fraudService,
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
    public function getUserDirectories(Wallet $wallet): array
    {
        $userId = (string) $wallet->user_id;
        $iso    = strtoupper($wallet->currency->iso_code);

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
     * Recibe la wallet ya resuelta y verificada desde el controller.
     */
    public function requestDeposit(
        Wallet  $wallet,
        float   $amount,
        string  $receipt,
        ?string $reference = null,
        ?string $notes = null,
    ): Wallet {
        if ($wallet->isBlocked()) {
            throw new \DomainException('La billetera está bloqueada.');
        }

        // Checks antifraude
        $this->fraudService->checkDepositVelocity((string) $wallet->user_id);
        $this->fraudService->checkDoubleSubmit((string) $wallet->id, $amount, WalletMovement::TYPE_DEPOSIT);
        if ($reference) {
            $this->fraudService->checkDuplicateReference((string) $wallet->id, $reference);
        }

        if ($this->getPendingOperation((string) $wallet->id, WalletMovement::TYPE_DEPOSIT)) {
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
    /**
     * Registrar solicitud de retiro.
     * Recibe la wallet ya resuelta y verificada desde el controller.
     */
    public function requestWithdraw(
        Wallet  $wallet,
        float   $amount,
        string  $directoryId,
        string  $directoryType,
        ?string $notes = null,
    ): Wallet {
        $withdrawalMovement = null;

        // Checks antifraude y límites ANTES de abrir la transacción
        $this->fraudService->checkWithdrawalVelocity((string) $wallet->user_id);
        $this->fraudService->checkDoubleSubmit((string) $wallet->id, $amount, WalletMovement::TYPE_WITHDRAWAL);
        $kycLevel = (int) ($wallet->user?->kyc_level ?? 0);
        $this->withdrawalLimitService->assertWithinLimits($wallet, $amount, $kycLevel);

        $wallet = DB::transaction(function () use ($wallet, $amount, $directoryId, $directoryType, $notes, &$withdrawalMovement) {
            $walletId = (string) $wallet->id;

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
    /**
     * Descuenta la entrada a una sala de Trivia y registra el movimiento TRIVIA_ENTRY.
     * La wallet se pasa ya resuelta (sin aceptar walletId externo).
     */
    public function recordTriviaEntry(Wallet $wallet, float $amount, string $description = 'Entrada sala Trivia'): Wallet
    {
        $result = DB::transaction(function () use ($wallet, $amount, $description) {
            $walletId = (string) $wallet->id;

            if (! $wallet->hasSufficientBalance($amount)) {
                throw new \DomainException('Saldo insuficiente para unirte a esta sala.');
            }

            $balanceBefore = (float) $wallet->balance;

            $this->walletRepository->decrementBalance($walletId, $amount);
            $wallet->refresh();

            $this->movementRepository->record([
                'wallet_id'      => $walletId,
                'user_id'        => (string) $wallet->user_id,
                'type'           => WalletMovement::TYPE_TRIVIA_ENTRY,
                'status'         => WalletMovement::STATUS_COMPLETED,
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => (float) $wallet->balance,
                'description'    => $description,
            ]);

            return $wallet;
        });

        $this->broadcastWalletUpdated($result, WalletMovement::TYPE_TRIVIA_ENTRY, $amount);

        return $result;
    }

    /**
     * Acredita un premio (Bingo, Trivia o cualquier juego futuro) al ganador.
     * La wallet se recibe ya resuelta — nunca acepta un ID externo.
     *
     * @param string $gameId    ID de la sala/juego que originó el premio.
     * @param string $prizeType WalletMovement::PRIZE_TYPE_DIGITAL | PRIZE_TYPE_PHYSICAL.
     */
    public function recordPrize(
        Wallet $wallet,
        float $amount,
        string $gameId,
        string $description = 'Premio ganado',
        string $prizeType = WalletMovement::PRIZE_TYPE_DIGITAL,
    ): WalletMovement {
        $movement = null;

        DB::transaction(function () use ($wallet, $amount, $description, $gameId, $prizeType, &$movement) {
            $walletId = (string) $wallet->id;

            $balanceBefore = (float) $wallet->balance;

            $this->walletRepository->incrementBalance($walletId, $amount);
            $wallet->refresh();

            $movement = $this->movementRepository->record([
                'wallet_id'      => $walletId,
                'user_id'        => (string) $wallet->user_id,
                'type'           => WalletMovement::TYPE_PRIZE,
                'status'         => WalletMovement::STATUS_COMPLETED,
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => (float) $wallet->balance,
                'description'    => $description,
                'metadata'       => ['game_id' => $gameId, 'prize_type' => $prizeType],
            ]);
        });

        $this->broadcastWalletUpdated($wallet, WalletMovement::TYPE_PRIZE, $amount);

        return $movement;
    }

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
     * Reembolsa un monto a la wallet de un usuario por cancelación de sala u otro motivo.
     * Registra el movimiento como TYPE_REFUND, separándolo de premios en reportes.
     */
    public function recordRefund(
        Wallet $wallet,
        float $amount,
        string $gameId,
        string $description = 'Reembolso',
    ): WalletMovement {
        $movement = null;

        DB::transaction(function () use ($wallet, $amount, $gameId, $description, &$movement) {
            $walletId      = (string) $wallet->id;
            $balanceBefore = (float) $wallet->balance;

            $this->walletRepository->incrementBalance($walletId, $amount);
            $wallet->refresh();

            $movement = $this->movementRepository->record([
                'wallet_id'      => $walletId,
                'user_id'        => (string) $wallet->user_id,
                'type'           => WalletMovement::TYPE_REFUND,
                'status'         => WalletMovement::STATUS_COMPLETED,
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => (float) $wallet->balance,
                'description'    => $description,
                'metadata'       => ['game_id' => $gameId],
            ]);
        });

        $this->broadcastWalletUpdated($wallet, WalletMovement::TYPE_REFUND, $amount);

        return $movement;
    }

    /**
     * Acredita saldo por acción administrativa.
     * Wallet findOrCreate + lockForUpdate + movimiento ocurren en una sola transacción,
     * eliminando la race condition de creación concurrente de wallets.
     */
    public function recordAdminDeposit(\App\Models\User $user, float $amount, string $description = 'Carga de saldo por administrador'): Wallet
    {
        $wallet = DB::transaction(function () use ($user, $amount, $description) {
            // lockForUpdate sobre la wallet evita doble carga concurrente
            $locked = Wallet::lockForUpdate()
                ->where('user_id', $user->id)
                ->first();

            if (!$locked) {
                $currency = \App\Models\Currency::where('iso_code', 'USD')->firstOrFail();
                $locked = $user->wallets()->create([
                    'currency_id' => $currency->id,
                    'balance'     => 0,
                    'is_active'   => true,
                ]);
            }

            $walletId      = (string) $locked->id;
            $balanceBefore = (float) $locked->balance;

            $this->walletRepository->incrementBalance($walletId, $amount);
            $locked->refresh();

            $this->movementRepository->record([
                'wallet_id'      => $walletId,
                'user_id'        => (string) $locked->user_id,
                'type'           => WalletMovement::TYPE_DEPOSIT,
                'status'         => WalletMovement::STATUS_COMPLETED,
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => (float) $locked->balance,
                'description'    => $description,
            ]);

            return $locked;
        });

        $this->broadcastWalletUpdated($wallet, WalletMovement::TYPE_DEPOSIT, $amount);

        return $wallet;
    }

    /**
     * Movimientos paginados de una billetera del usuario autenticado.
     */
    public function getMovements(Wallet $wallet, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->movementRepository->getByWallet((string) $wallet->id, $perPage);
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
     * Aprobar un movimiento PENDING de tipo custom (catálogo MovementType,
     * requires_approval=true) sin lógica específica propia: si is_credit es
     * true el saldo aún no se tocó al crearse (igual que DEPOSIT) y se
     * acredita ahora; si es false ya fue descontado al solicitarse (igual
     * que WITHDRAWAL) y aquí solo se marca COMPLETED.
     */
    public function approveGeneric(WalletMovement $movement, ?string $adminNotes = null): WalletMovement
    {
        if ($movement->status !== WalletMovement::STATUS_PENDING) {
            throw new \DomainException('Solo se pueden aprobar movimientos en estado PENDING.');
        }

        $isCredit = $movement->isCredit();

        $updated = DB::transaction(function () use ($movement, $adminNotes, $isCredit) {
            if ($isCredit) {
                $this->walletRepository->incrementBalance((string) $movement->wallet_id, (float) $movement->amount);
                $movement->wallet->refresh();
            }

            $movement->update([
                'status'        => WalletMovement::STATUS_COMPLETED,
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
     * Rechazar un movimiento PENDING de tipo custom: si is_credit es false el
     * saldo fue descontado al solicitarse y se reintegra aquí (igual que
     * WITHDRAWAL); si es true nunca se tocó el saldo y no hay nada que revertir.
     */
    public function rejectGeneric(WalletMovement $movement, ?string $adminNotes = null): WalletMovement
    {
        if ($movement->status !== WalletMovement::STATUS_PENDING) {
            throw new \DomainException('Solo se pueden rechazar movimientos en estado PENDING.');
        }

        $isCredit = $movement->isCredit();

        $updated = DB::transaction(function () use ($movement, $adminNotes, $isCredit) {
            if (! $isCredit) {
                $this->walletRepository->incrementBalance((string) $movement->wallet_id, (float) $movement->amount);
                $movement->wallet->refresh();
            }

            $movement->update([
                'status'       => WalletMovement::STATUS_REJECTED,
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

        // WebSocket: notifica al canal privado del usuario (best-effort — no bloquea si Reverb está caído)
        try {
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
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[WalletService] broadcastAndNotifyResult falló (WebSocket no disponible): ' . $e->getMessage());
        }

        // Notificación persistente en tabla notifications
        $movementId = (string) $movement->id;

        if ($movement->type === WalletMovement::TYPE_DEPOSIT) {
            $this->notificationService->createDepositResultNotification(
                userId:         $userId,
                status:         $status,
                amount:         $amount,
                currencySymbol: $currencySymbol,
                adminNotes:     $adminNotes,
                movementId:     $movementId,
            );
        } elseif ($movement->type === WalletMovement::TYPE_WITHDRAWAL) {
            $this->notificationService->createWithdrawalResultNotification(
                userId:         $userId,
                status:         $status,
                amount:         $amount,
                currencySymbol: $currencySymbol,
                adminNotes:     $adminNotes,
                movementId:     $movementId,
            );
        } else {
            $type = \App\Models\MovementType::catalog()->get($movement->type);
            $this->notificationService->createMovementResultNotification(
                userId:         $userId,
                movementType:   $movement->type,
                status:         $status,
                amount:         $amount,
                currencySymbol: $currencySymbol,
                typeLabel:      $type?->label ?? $movement->type,
                adminNotes:     $adminNotes,
                movementId:     $movementId,
            );
        }

        // Notifica a los admins que el pendiente fue procesado para que
        // actualicen su contador en tiempo real sin recargar la página.
        try {
            $pendingCount = WalletMovement::where('status', WalletMovement::STATUS_PENDING)->count();
            broadcast(new \App\Events\Wallet\WalletMovementProcessed(
                movementId:   (string) $movement->id,
                movementType: $movement->type,
                status:       $status,
                pendingCount: $pendingCount,
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[WalletService] WalletMovementProcessed broadcast falló: ' . $e->getMessage());
        }
    }

    /**
     * Emite el evento WebSocket con el nuevo saldo.
     */
    private function broadcastWalletUpdated(Wallet $wallet, string $type, float $amount): void
    {
        try {
            broadcast(new WalletUpdated(
                userId:         (string) $wallet->user_id,
                walletId:       (string) $wallet->id,
                balance:        (float)  $wallet->balance,
                currencySymbol: $wallet->currency->symbol ?? '',
                movementType:   $type,
                amount:         $amount,
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[WalletService] broadcastWalletUpdated falló (WebSocket no disponible): ' . $e->getMessage());
        }

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

        // 1. Broadcast WebSocket al canal de admins (best-effort)
        try {
            broadcast(new WalletMovementRequested(
                movementId:     $movementId,
                userId:         (string) $wallet->user_id,
                userName:       $userName,
                movementType:   $type,
                amount:         $amount,
                currencySymbol: $currency?->symbol ?? '',
                currencyIso:    $currency?->iso_code ?? '',
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[WalletService] broadcastAdminWalletMovement falló (WebSocket no disponible): ' . $e->getMessage());
        }

        // 2. Notificación persistente en DB para cada admin/superadmin
        $admins = \App\Models\User::whereHas(
            'roles',
            fn($q) => $q->whereIn('name', ['admin', 'superadmin'])
        )->get();

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
     * Resuelve la wallet ACTIVA del usuario autenticado para una moneda dada.
     * Nunca acepta un wallet_id externo — el cliente solo indica la moneda.
     */
    public function findOwnedWalletByCurrency(string $currencyId): Wallet
    {
        $wallet = Wallet::with(['currency', 'user'])
            ->where('user_id', (string) Auth::id())
            ->where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->first();

        if (! $wallet) {
            throw new \DomainException('No tienes una billetera activa para esa moneda.');
        }

        return $wallet;
    }

    /**
     * Obtiene una wallet del usuario autenticado por su ID (uso interno / admin).
     *
     * Siempre se llama dentro de un DB::transaction() (deposit/withdraw/recordCardPurchase)
     * — lockForUpdate() evita que dos operaciones concurrentes sobre la misma wallet
     * (p.ej. dos compras de cartones a la vez) lean el mismo balance antes de que la
     * primera haga commit, lo que permitiría dejar el balance en negativo.
     */
    private function findOwnedWallet(string $walletId): Wallet
    {
        $wallet = Wallet::with(['currency', 'user'])->lockForUpdate()->findOrFail($walletId);

        if ((string) $wallet->user_id !== (string) Auth::id()) {
            abort(403, 'No autorizado.');
        }

        return $wallet;
    }
}
