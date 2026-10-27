<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Services;

use App\Domains\Wallet\Contracts\WalletMovementRepositoryInterface;
use App\Domains\Wallet\Contracts\WalletRepositoryInterface;
use App\Domains\Wallet\Events\WalletMovementAssigned;
use App\Domains\Wallet\Events\WalletMovementRequested;
use App\Domains\Wallet\Events\WalletUpdated;
use App\Domains\Wallet\Exceptions\WalletDomainException;
use App\Domains\Wallet\Jobs\DispatchWalletNotificationJob;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletMovement;
use App\Events\Games\Trivia\TriviaEntryPurchased;
use App\Models\Currency;
use App\Models\Directories\Crypto\Binance\BinanceAccount;
use App\Models\Directories\Fiat\Brazil\PixKey;
use App\Models\Directories\Fiat\Venezuela\PagoMovil;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Orquestador central de wallet: depósitos, retiros, entradas y premios de
 * cualquier juego.
 *
 * REGLA DE ARQUITECTURA — cruce MySQL (wallets/wallet_movements) ↔ Postgres
 * (pgsql_trivia, y cualquier conexión de juego futura que viva en otra BD):
 * NUNCA envolver una escritura contra 'pgsql_trivia' (u otra conexión
 * distinta a la default) dentro del mismo DB::transaction() que escribe
 * wallet_movements. DB::transaction() de Laravel solo es atómico dentro de
 * una única conexión — no existe two-phase commit entre MySQL y Postgres
 * aquí, así que un rollback en una base NO revierte lo ya escrito en la otra.
 *
 * El patrón ya usado en este archivo (ver recordTriviaEntry()/recordPrize())
 * es el estándar a seguir para cualquier juego nuevo que cruce conexiones:
 *   1. Escribir primero en la base "dueña" del evento de origen.
 *   2. Aplicar el efecto en wallet_movements con una idempotency key
 *      determinística (ej. "trivia_prize:{room_id}:{user_id}"), para que un
 *      retry tras fallo parcial no acredite/debite dos veces.
 *   3. Si el efecto se aplica vía Job en cola (no inline), dejar que el
 *      mecanismo de failed_jobs ya monitoreado (panel Jobs/Scheduler) sea la
 *      red de reintento — no construir un outbox propio por juego.
 *   4. Cualquier discrepancia detectada entre ambas bases se resuelve con un
 *      comando de reconciliación de solo-lectura/alerta, nunca con
 *      auto-corrección silenciosa de saldo.
 */
final class WalletService
{
    /**
     * Minutos que dura el lock de asignación por inactividad. Pasado este
     * tiempo desde assigned_at, cualquier admin puede volver a tomar el
     * caso sin necesidad de que el dueño original lo libere manualmente —
     * evita que un caso quede bloqueado indefinidamente si esa persona se
     * desconecta sin liberar.
     */
    private const ASSIGNMENT_LOCK_MINUTES = 30;

    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly WalletMovementRepositoryInterface $movementRepository,
        private readonly NotificationService $notificationService,
        private readonly WalletLimitService $walletLimitService,
        private readonly WalletFraudService $fraudService,
    ) {
    }

    /**
     * Aprobar un depósito PENDING: acredita el monto al saldo y marca COMPLETED.
     *
     * El check de $movement->type se hace fuera de la transacción (no cambia
     * con la concurrencia), pero el status PENDING se revalida DENTRO, bajo
     * lockForUpdate() sobre el propio movimiento — antes se leía sin lock
     * antes de abrir la transacción, así que dos admins aprobando el mismo
     * depósito casi a la vez podían ambos pasar el check y ambos ejecutar
     * incrementBalance(): doble acreditación real de un depósito.
     */
    public function approveDeposit(WalletMovement $movement, ?string $adminNotes = null) : WalletMovement
    {
        if ($movement->type !== WalletMovement::TYPE_DEPOSIT) {
            throw WalletDomainException::movementNotDeposit();
        }

        $this->guardNotLockedByOther($movement);

        $updated = DB::transaction(function() use ($movement, $adminNotes) {
            $locked = WalletMovement::where('id', $movement->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== WalletMovement::STATUS_PENDING) {
                throw WalletDomainException::approvalRequiresPendingStatus();
            }

            // pending_debt se cobra de cualquier crédito entrante, incluida
            // la aprobación de un depósito — ver recordRefund() para el porqué.
            $netAmount = $this->settlePendingDebt($locked->wallet, (float) $locked->amount);
            if ($netAmount > 0) {
                $this->walletRepository->incrementBalance((string) $locked->wallet_id, $netAmount);
            }
            $locked->wallet->refresh();

            $locked->update([
                'status' => WalletMovement::STATUS_COMPLETED,
                'description' => 'Depósito acreditado',
                'balance_after' => (float) $locked->wallet->balance,
                'admin_notes' => $adminNotes,
                'processed_by' => (string) Auth::id(),
                'processed_at' => now(),
            ]);

            return $locked->fresh(['wallet', 'wallet.currency']);
        });

        $this->broadcastAndNotifyResult($updated, WalletMovement::STATUS_COMPLETED, $adminNotes);

        return $updated;
    }

    /**
     * Aprobar un movimiento PENDING de tipo custom (catálogo MovementType,
     * requires_approval=true) sin lógica específica propia: si is_credit es
     * true el saldo aún no se tocó al crearse (igual que DEPOSIT) y se
     * acredita ahora; si es false ya fue descontado al solicitarse (igual
     * que WITHDRAWAL) y aquí solo se marca COMPLETED.
     */
    public function approveGeneric(WalletMovement $movement, ?string $adminNotes = null) : WalletMovement
    {
        $this->guardNotLockedByOther($movement);

        $updated = DB::transaction(function() use ($movement, $adminNotes) {
            $locked = WalletMovement::where('id', $movement->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== WalletMovement::STATUS_PENDING) {
                throw WalletDomainException::approvalRequiresPendingStatus();
            }

            if ($locked->isCredit()) {
                // pending_debt se cobra de cualquier crédito entrante — ver
                // recordRefund() para el porqué.
                $netAmount = $this->settlePendingDebt($locked->wallet, (float) $locked->amount);
                if ($netAmount > 0) {
                    $this->walletRepository->incrementBalance((string) $locked->wallet_id, $netAmount);
                }
                $locked->wallet->refresh();
            }

            $locked->update([
                'status' => WalletMovement::STATUS_COMPLETED,
                'balance_after' => (float) $locked->wallet->balance,
                'admin_notes' => $adminNotes,
                'processed_by' => (string) Auth::id(),
                'processed_at' => now(),
            ]);

            return $locked->fresh(['wallet', 'wallet.currency']);
        });

        $this->broadcastAndNotifyResult($updated, WalletMovement::STATUS_COMPLETED, $adminNotes);

        return $updated;
    }

    /**
     * Aprobar un retiro PENDING: el saldo ya fue descontado, solo marca COMPLETED.
     *
     * Aunque este método no mueve balance (ya se descontó al solicitarse),
     * se bloquea el movimiento igual que approveDeposit()/approveGeneric()
     * para mantener el mismo patrón y evitar que dos aprobaciones
     * concurrentes generen notificaciones/broadcasts duplicados.
     */
    public function approveWithdrawal(WalletMovement $movement, ?string $adminNotes = null) : WalletMovement
    {
        if ($movement->type !== WalletMovement::TYPE_WITHDRAWAL) {
            throw WalletDomainException::movementNotWithdrawal();
        }

        $this->guardNotLockedByOther($movement);

        $updated = DB::transaction(function() use ($movement, $adminNotes) {
            $locked = WalletMovement::where('id', $movement->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== WalletMovement::STATUS_PENDING) {
                throw WalletDomainException::approvalRequiresPendingStatus();
            }

            $locked->update([
                'status' => WalletMovement::STATUS_COMPLETED,
                'description' => 'Retiro procesado',
                'admin_notes' => $adminNotes,
                'processed_by' => (string) Auth::id(),
                'processed_at' => now(),
            ]);

            return $locked->fresh(['wallet', 'wallet.currency']);
        });

        $this->broadcastAndNotifyResult($updated, WalletMovement::STATUS_COMPLETED, $adminNotes);

        return $updated;
    }

    /**
     * "Tomar" un movimiento PENDING como propio — evita que dos admins
     * trabajen el mismo caso a la vez. Rechaza con 409 si ya está asignado a
     * OTRO admin y el lock sigue vigente, salvo que el actor sea superadmin
     * (puede forzar la reasignación tomando el caso él mismo).
     */
    public function assignMovement(WalletMovement $movement) : WalletMovement
    {
        $this->guardNotLockedByOther($movement);

        $actor = Auth::user();

        $movement->forceFill([
            'assigned_to_user_id' => $actor->id,
            'assigned_at' => now(),
        ])->save();

        $this->broadcastMovementAssigned($movement->fresh(), $actor, assigned: true);

        return $movement->fresh(['wallet', 'wallet.currency', 'assignedTo']);
    }

    /**
     * Registrar solicitud de depósito.
     *
     * @deprecated Usar requestDeposit()
     */
    public function deposit(string $walletId, float $amount) : Wallet
    {
        $movementId = null;

        $wallet = DB::transaction(function() use ($walletId, $amount, &$movementId) {
            $wallet = $this->findOwnedWallet($walletId);

            if ($wallet->isBlocked()) {
                throw WalletDomainException::walletBlocked();
            }

            $balanceBefore = (float) $wallet->balance;

            $this->walletRepository->incrementBalance($walletId, $amount);
            $wallet->refresh();

            $movement = $this->movementRepository->record([
                'wallet_id' => (string) $wallet->id,
                'user_id' => (string) $wallet->user_id,
                'type' => WalletMovement::TYPE_DEPOSIT,
                'status' => WalletMovement::STATUS_PENDING,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => (float) $wallet->balance,
                'description' => 'Depósito manual',
            ]);
            $movementId = (string) $movement->id;

            return $wallet;
        });

        // Broadcast FUERA de la transacción: garantiza que el commit ya ocurrió
        $this->broadcastWalletUpdated($wallet, WalletMovement::TYPE_DEPOSIT, $amount, $movementId);

        return $wallet;
    }

    /**
     * Busca la wallet activa de un usuario para una moneda dada.
     * Devuelve null si no existe o está inactiva — nunca lanza excepción.
     * Centraliza el guard duplicado en BingoRoomCrudService y BingoPrizePayoutService.
     */
    public function findActiveWallet(string $userId, string $currencyId) : ?Wallet
    {
        $wallet = $this->walletRepository->findWalletByUserAndCurrency($userId, $currencyId);

        return ($wallet && $wallet->isActive()) ? $wallet : null;
    }

    /**
     * Resuelve la wallet ACTIVA del usuario autenticado para una moneda dada.
     * Nunca acepta un wallet_id externo — el cliente solo indica la moneda.
     */
    public function findOwnedWalletByCurrency(string $currencyId) : Wallet
    {
        $wallet = Wallet::with(['currency', 'user'])
            ->where('user_id', (string) Auth::id())
            ->where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->first();

        if (! $wallet) {
            throw WalletDomainException::noActiveWalletForCurrency();
        }

        return $wallet;
    }

    /**
     * Registrar una compra de cartones en la billetera.
     *
     * @param string $walletId    ID de la billetera
     * @param float  $amount      Monto de la compra
     * @param string $description Descripción de la compra (opcional)
     *
     * @throws DomainException Si no hay saldo suficiente
     */
    /**
     * Descuenta la entrada a una sala de Trivia y registra el movimiento TRIVIA_ENTRY.
     * La wallet se pasa ya resuelta (sin aceptar walletId externo).
     *
     * $roomId/$roomName son opcionales para no romper otros callers futuros,
     * pero si se pasan disparan TriviaEntryPurchased hacia admins.activity —
     * mismo patrón que BingoCardSold para ventas de cartones.
     */
    /**
     * Busca un cobro de entrada de Trivia ya confirmado para que el flujo de
     * inscripción pueda recuperar el monto y la moneda originales al reintentar
     * después de un fallo parcial (la tasa o la tarifa de la sala pudo cambiar).
     */
    public function findTriviaEntryMovement(string $idempotencyKey) : ?WalletMovement
    {
        return WalletMovement::with('wallet.currency')
            ->where('idempotency_key', $idempotencyKey)
            ->where('type', WalletMovement::TYPE_TRIVIA_ENTRY)
            ->first();
    }

    /**
     * Movimientos paginados de una billetera del usuario autenticado.
     */
    public function getMovements(Wallet $wallet, int $perPage = 20) : \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->movementRepository->getByWallet((string) $wallet->id, $perPage);
    }

    /**
     * Verifica si la billetera tiene una solicitud de depósito o retiro en cola (PENDING).
     * Retorna el movimiento pendiente o null.
     */
    public function getPendingOperation(string $walletId, string $type) : ?WalletMovement
    {
        return WalletMovement::where('wallet_id', $walletId)
            ->where('type', $type)
            ->where('status', WalletMovement::STATUS_PENDING)
            ->latest()
            ->first();
    }

    /**
     * Wallets activas del usuario autenticado con moneda cargada.
     */
    public function getPlayerWallets() : array
    {
        $userId = (string) Auth::id();

        return $this->walletRepository
            ->getWalletsByUser($userId, ['status' => 'ACTIVE'])
            ->map(fn (Wallet $w) => [
                'id' => (string) $w->id,
                'balance' => (float) $w->balance,
                'bonus_balance' => (float) $w->bonus_balance,
                'playable_balance' => $w->getPlayableBalance(),
                'status' => $w->status,
                'currency_id' => (string) $w->currency_id,
                'currency' => [
                    'iso_code' => $w->currency->iso_code,
                    'symbol' => $w->currency->symbol,
                    'name' => $w->currency->name,
                    'decimal_places' => $w->currency->decimal_places ?? 2,
                ],
            ])
            ->values()
            ->toArray();
    }

    /**
     * Directorios de pago del usuario filtrados según la moneda de la billetera.
     * VES → PagoMovil | BRL → PIX | USDT → Binance
     */
    public function getUserDirectories(Wallet $wallet) : array
    {
        $userId = (string) $wallet->user_id;
        $iso = strtoupper($wallet->currency->iso_code);

        return match (true) {
            in_array($iso, ['VES', 'VED', 'BSS'], true) => $this->getPagoMovilDirectories($userId),
            $iso === 'BRL' => $this->getPixDirectories($userId),
            in_array($iso, ['USDT', 'BTC', 'ETH'], true) => $this->getBinanceDirectories($userId),
            default => [],
        };
    }

    /**
     * Acredita saldo por acción administrativa.
     * Wallet findOrCreate + lockForUpdate + movimiento ocurren en una sola transacción,
     * eliminando la race condition de creación concurrente de wallets.
     */
    public function recordAdminDeposit(User $user, float $amount, string $description = 'Carga de saldo por administrador') : Wallet
    {
        $this->assertPositiveAmount($amount);

        $movementId = null;

        $wallet = DB::transaction(function() use ($user, $amount, $description, &$movementId) {
            // lockForUpdate sobre la wallet evita doble carga concurrente
            $locked = Wallet::lockForUpdate()
                ->where('user_id', $user->id)
                ->first();

            if (! $locked) {
                $currency = Currency::where('iso_code', 'USD')->firstOrFail();
                $locked = $user->wallets()->create([
                    'currency_id' => $currency->id,
                    'balance' => 0,
                    'status' => 'ACTIVE',
                ]);
            }

            $walletId = (string) $locked->id;
            $balanceBefore = (float) $locked->balance;

            // pending_debt se cobra de cualquier crédito entrante, incluida
            // una carga administrativa — ver recordRefund() para el porqué.
            $netAmount = $this->settlePendingDebt($locked, $amount);

            if ($netAmount > 0) {
                $this->walletRepository->incrementBalance($walletId, $netAmount);
            }
            $locked->refresh();

            $movement = $this->movementRepository->record([
                'wallet_id' => $walletId,
                'user_id' => (string) $locked->user_id,
                'type' => WalletMovement::TYPE_DEPOSIT,
                'status' => WalletMovement::STATUS_COMPLETED,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => (float) $locked->balance,
                'description' => $description,
                'metadata' => $netAmount !== $amount ? ['pending_debt_settled' => round($amount - $netAmount, 2)] : null,
            ]);
            $movementId = (string) $movement->id;

            return $locked;
        });

        $this->broadcastWalletUpdated($wallet, WalletMovement::TYPE_DEPOSIT, $amount, $movementId, $description);

        return $wallet;
    }

    /**
     * Registra la compra de cartones, consumiendo bonus_balance antes que
     * balance real. Devuelve el desglose de cuánto salió de cada columna para
     * que el caller (ej. BingoCardGenerateService) pueda propagarlo al lote de
     * cartones — necesario para reembolsar proporcionalmente si la sala se
     * cancela (ver recordRefund()).
     *
     * @return array{wallet: Wallet, bonus_spent: float, balance_spent: float}
     */
    public function recordCardPurchase(string $walletId, float $amount, string $description = 'Compra de cartones') : array
    {
        $this->assertPositiveAmount($amount);

        $movementId = null;
        $split = ['bonus_spent' => 0.0, 'balance_spent' => 0.0];

        $wallet = DB::transaction(function() use ($walletId, $amount, $description, &$movementId, &$split) {
            $wallet = $this->findOwnedWallet($walletId);

            if (! $wallet->hasSufficientPlayableBalance($amount)) {
                throw WalletDomainException::insufficientBalanceForCardPurchase();
            }

            $balanceBefore = (float) $wallet->balance;
            $bonusBalanceBefore = (float) $wallet->bonus_balance;

            $split = $this->walletRepository->consumeBonusFirst($walletId, $amount);
            $wallet->refresh();

            $movement = $this->movementRepository->record([
                'wallet_id' => (string) $wallet->id,
                'user_id' => (string) $wallet->user_id,
                'type' => WalletMovement::TYPE_PURCHASE,
                'status' => WalletMovement::STATUS_COMPLETED,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => (float) $wallet->balance,
                'description' => $description,
                'metadata' => [
                    'bonus_amount' => $split['bonus_spent'],
                    'balance_amount' => $split['balance_spent'],
                    'bonus_balance_before' => $bonusBalanceBefore,
                    'bonus_balance_after' => (float) $wallet->bonus_balance,
                ],
            ]);
            $movementId = (string) $movement->id;

            return $wallet;
        });

        // Broadcast FUERA de la transacción: garantiza que el commit ya ocurrió
        $this->broadcastWalletUpdated($wallet, WalletMovement::TYPE_PURCHASE, $amount, $movementId);

        return ['wallet' => $wallet, 'bonus_spent' => $split['bonus_spent'], 'balance_spent' => $split['balance_spent']];
    }

    /**
     * Acredita al creador de una sala de Bingo su comisión (y, en salas
     * Premio/Servicio puras, los fondos del premio que él pone) por las ventas
     * pagadas con billetera, al cerrarse la sala (BingoRoomCreatorPayoutService).
     * Mismo patrón idempotente que recordMarketplaceCredit(): la clave
     * determinística por sala+moneda evita pagar dos veces si el cierre se
     * reintenta (recuperación de salas trabadas).
     */
    public function recordCreatorCommission(
        Wallet $wallet,
        float $amount,
        string $description,
        string $idempotencyKey,
        array $metadata = [],
    ) : WalletMovement {
        $this->assertPositiveAmount($amount);

        $existing = WalletMovement::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        try {
            $movement = DB::transaction(function() use ($wallet, $amount, $description, $idempotencyKey, $metadata) {
                $locked = Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();
                $walletId = (string) $locked->id;
                $balanceBefore = (float) $locked->balance;

                // pending_debt se cobra de cualquier crédito entrante — ver recordRefund().
                $netAmount = $this->settlePendingDebt($locked, $amount);
                if ($netAmount > 0) {
                    $this->walletRepository->incrementBalance($walletId, $netAmount);
                }
                if ($netAmount !== $amount) {
                    $metadata['pending_debt_settled'] = round($amount - $netAmount, 2);
                }
                $locked->refresh();

                return $this->movementRepository->record([
                    'wallet_id' => $walletId,
                    'user_id' => (string) $locked->user_id,
                    'type' => WalletMovement::TYPE_CREATOR_COMMISSION,
                    'status' => WalletMovement::STATUS_COMPLETED,
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => (float) $locked->balance,
                    'description' => $description,
                    'idempotency_key' => $idempotencyKey,
                    'metadata' => $metadata ?: null,
                ]);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'idempotency_key')) {
                $existingMovement = WalletMovement::where('idempotency_key', $idempotencyKey)->first();
                if ($existingMovement) {
                    return $existingMovement;
                }
            }

            throw $e;
        }

        $this->broadcastWalletUpdated($wallet, WalletMovement::TYPE_CREATOR_COMMISSION, $amount, (string) $movement->id);

        return $movement;
    }

    /**
     * Acredita un depósito cripto ya confirmado on-chain (ver
     * DepositWatcher::process(), Fase 6). idempotencyKey determinística
     * ("crypto_deposit:{tx_hash}") es la defensa a nivel de aplicación;
     * crypto_transactions.tx_hash UNIQUE es la defensa real a nivel de BD
     * contra re-procesar la misma transacción on-chain dos veces — mismo
     * criterio de doble capa que el resto de idempotencia en este archivo.
     */
    public function recordCryptoDeposit(
        Wallet $wallet,
        float $amount,
        string $description,
        string $idempotencyKey,
    ) : WalletMovement {
        $this->assertPositiveAmount($amount);

        $existing = WalletMovement::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        try {
            $movement = DB::transaction(function() use ($wallet, $amount, $description, $idempotencyKey) {
                $walletId = (string) $wallet->id;
                $balanceBefore = (float) $wallet->balance;

                $netAmount = $this->settlePendingDebt($wallet, $amount);
                if ($netAmount > 0) {
                    $this->walletRepository->incrementBalance($walletId, $netAmount);
                }
                $wallet->refresh();

                return $this->movementRepository->record([
                    'wallet_id' => $walletId,
                    'user_id' => (string) $wallet->user_id,
                    'type' => WalletMovement::TYPE_CRYPTO_DEPOSIT,
                    'status' => WalletMovement::STATUS_COMPLETED,
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => (float) $wallet->balance,
                    'description' => $description,
                    'idempotency_key' => $idempotencyKey,
                ]);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'idempotency_key')) {
                $existingMovement = WalletMovement::where('idempotency_key', $idempotencyKey)->first();
                if ($existingMovement) {
                    return $existingMovement;
                }
            }

            throw $e;
        }

        $this->broadcastWalletUpdated($wallet, WalletMovement::TYPE_CRYPTO_DEPOSIT, $amount, (string) $movement->id);

        return $movement;
    }

    /**
     * Captura un WalletHold como gasto real: descuenta el saldo (bono
     * primero, igual que cualquier otro débito de juego) y registra el
     * WalletMovement correspondiente. Llamado por
     * WalletReservationService::capture() — este método NO gestiona el
     * ciclo de vida del hold en sí (eso vive en WalletReservationService),
     * solo el efecto sobre la wallet.
     */
    public function recordHoldCapture(Wallet $wallet, float $amount, string $movementType, ?string $description, string $holdId) : WalletMovement
    {
        $this->assertPositiveAmount($amount);

        $movementId = null;

        $wallet = DB::transaction(function() use ($wallet, $amount, $movementType, $description, $holdId, &$movementId) {
            $locked = Wallet::lockForUpdate()->findOrFail($wallet->id);

            if (! $locked->hasSufficientPlayableBalance($amount)) {
                throw WalletDomainException::insufficientBalanceForHoldCapture();
            }

            $walletId = (string) $locked->id;
            $balanceBefore = (float) $locked->balance;
            $bonusBalanceBefore = (float) $locked->bonus_balance;

            $split = $this->walletRepository->consumeBonusFirst($walletId, $amount);
            $locked->refresh();

            $movement = $this->movementRepository->record([
                'wallet_id' => $walletId,
                'user_id' => (string) $locked->user_id,
                'type' => $movementType,
                'status' => WalletMovement::STATUS_COMPLETED,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => (float) $locked->balance,
                'description' => $description,
                'reference_id' => $holdId,
                'metadata' => [
                    'bonus_amount' => $split['bonus_spent'],
                    'balance_amount' => $split['balance_spent'],
                    'bonus_balance_before' => $bonusBalanceBefore,
                    'bonus_balance_after' => (float) $locked->bonus_balance,
                    'wallet_hold_id' => $holdId,
                ],
            ]);
            $movementId = (string) $movement->id;

            return $locked;
        });

        $this->broadcastWalletUpdated($wallet, $movementType, $amount, $movementId, $description);

        return WalletMovement::findOrFail($movementId);
    }

    /**
     * Acredita al vendedor el neto de una venta del Marketplace P2P (precio
     * menos comisión de plataforma, ya calculado por el caller —
     * EscrowService::release()). Mismo patrón de idempotencia que
     * recordPrize(): idempotencyKey determinística
     * ("marketplace_escrow:{escrowId}:seller_credit") protege contra doble
     * acreditación si el settlement del escrow se reintenta tras un fallo
     * parcial. No es un "premio" — se modela con su propio tipo
     * (MARKETPLACE_SALE, is_credit=true en el catálogo MovementType) para
     * que el ledger distinga claramente ingresos de juego de ingresos de
     * marketplace en reportes/auditoría.
     */
    public function recordMarketplaceCredit(
        Wallet $wallet,
        float $amount,
        string $description,
        string $idempotencyKey,
    ) : WalletMovement {
        $this->assertPositiveAmount($amount);

        $existing = WalletMovement::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        try {
            $movement = DB::transaction(function() use ($wallet, $amount, $description, $idempotencyKey) {
                $walletId = (string) $wallet->id;
                $balanceBefore = (float) $wallet->balance;

                // pending_debt se cobra de cualquier crédito entrante, incluida
                // una venta del marketplace — ver recordRefund() para el porqué.
                $netAmount = $this->settlePendingDebt($wallet, $amount);
                if ($netAmount > 0) {
                    $this->walletRepository->incrementBalance($walletId, $netAmount);
                }
                $wallet->refresh();

                return $this->movementRepository->record([
                    'wallet_id' => $walletId,
                    'user_id' => (string) $wallet->user_id,
                    'type' => WalletMovement::TYPE_MARKETPLACE_SALE,
                    'status' => WalletMovement::STATUS_COMPLETED,
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => (float) $wallet->balance,
                    'description' => $description,
                    'idempotency_key' => $idempotencyKey,
                ]);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'idempotency_key')) {
                $existingMovement = WalletMovement::where('idempotency_key', $idempotencyKey)->first();
                if ($existingMovement) {
                    return $existingMovement;
                }
            }

            throw $e;
        }

        $this->broadcastWalletUpdated($wallet, WalletMovement::TYPE_MARKETPLACE_SALE, $amount, (string) $movement->id);

        return $movement;
    }

    /**
     * Acredita un premio (Bingo, Trivia o cualquier juego futuro) al ganador.
     * La wallet se recibe ya resuelta — nunca acepta un ID externo.
     *
     * @param string      $gameId         ID de la sala/juego que originó el premio
     * @param string      $prizeType      walletMovement::PRIZE_TYPE_DIGITAL | PRIZE_TYPE_PHYSICAL
     * @param string|null $idempotencyKey Clave determinística (ej. "trivia_prize:{room_id}:{user_id}").
     *                                    Si ya existe un movimiento con esta clave, se retorna sin acreditar de
     *                                    nuevo — necesario para juegos cuyo registro de premio vive en una BD
     *                                    distinta a wallet_movements (ej. Trivia en pgsql_trivia), donde no hay
     *                                    transacción conjunta ni lockForUpdate que prevenga doble pago en un retry.
     * @param bool        $withdrawable   true (default) acredita a balance retirable
     *                                    con type=PRIZE, igual que siempre. false acredita a bonus_balance (no
     *                                    retirable, solo usable dentro de la app) con type=BONUS_PRIZE. La
     *                                    decisión de negocio de cuándo un premio es bono queda fuera de este
     *                                    método — es un punto de extensión para el caller (ver GamePrizeService).
     */
    public function recordPrize(
        Wallet $wallet,
        float $amount,
        string $gameId,
        string $description = 'Premio ganado',
        string $prizeType = WalletMovement::PRIZE_TYPE_DIGITAL,
        ?string $idempotencyKey = null,
        bool $withdrawable = true,
    ) : WalletMovement {
        $this->assertPositiveAmount($amount);

        if ($idempotencyKey !== null) {
            $existing = WalletMovement::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }
        }

        try {
            $movement = DB::transaction(function() use ($wallet, $amount, $description, $gameId, $prizeType, $idempotencyKey, $withdrawable) {
                $walletId = (string) $wallet->id;
                $balanceBefore = (float) $wallet->balance;
                $bonusBalanceBefore = (float) $wallet->bonus_balance;

                $metadata = ['game_id' => $gameId, 'prize_type' => $prizeType, 'withdrawable' => $withdrawable];

                if ($withdrawable) {
                    // pending_debt se cobra de cualquier crédito entrante,
                    // incluido un premio ganado — ver recordRefund() para el
                    // porqué. Solo aplica a la porción "balance" (retirable);
                    // premios de bono (withdrawable=false) no se tocan.
                    $netAmount = $this->settlePendingDebt($wallet, $amount);
                    if ($netAmount > 0) {
                        $this->walletRepository->incrementBalance($walletId, $netAmount);
                    }
                    if ($netAmount !== $amount) {
                        $metadata['pending_debt_settled'] = round($amount - $netAmount, 2);
                    }
                } else {
                    $this->walletRepository->incrementBonusBalance($walletId, $amount);
                }
                $wallet->refresh();

                if (! $withdrawable) {
                    $metadata['bonus_balance_before'] = $bonusBalanceBefore;
                    $metadata['bonus_balance_after'] = (float) $wallet->bonus_balance;
                }

                return $this->movementRepository->record([
                    'wallet_id' => $walletId,
                    'user_id' => (string) $wallet->user_id,
                    'type' => $withdrawable ? WalletMovement::TYPE_PRIZE : WalletMovement::TYPE_BONUS_PRIZE,
                    'status' => WalletMovement::STATUS_COMPLETED,
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => (float) $wallet->balance,
                    'description' => $description,
                    'idempotency_key' => $idempotencyKey,
                    'metadata' => $metadata,
                ]);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Carrera entre el check de arriba y el insert: otro proceso ganó y
            // ya acreditó esta misma clave. La wallet no se acreditó dos veces
            // porque incrementBalance() de ESTA llamada revierte con la transacción.
            //
            // Solo se trata como carrera de idempotencia si (a) el mensaje señala
            // el índice único de esa columna Y (b) el registro realmente existe —
            // cualquier otro fallo de la misma transacción (ej. FK, NOT NULL) no
            // debe enmascararse como "ya pagado", o firstOrFail() lanza una
            // excepción distinta que oculta la causa real.
            if ($idempotencyKey !== null && str_contains($e->getMessage(), 'idempotency_key')) {
                $existingMovement = WalletMovement::where('idempotency_key', $idempotencyKey)->first();
                if ($existingMovement) {
                    return $existingMovement;
                }
            }

            throw $e;
        }

        $this->broadcastWalletUpdated($wallet, WalletMovement::TYPE_PRIZE, $amount, (string) $movement->id);

        return $movement;
    }

    /**
     * Revierte un premio ya acreditado (disputa post-pago, ver
     * GamePrizeService::disputeAndReverse()). El débito se TOPA al balance
     * disponible — la BD tiene un CHECK CONSTRAINT real
     * (chk_wallets_balance_nonneg) que impide balance negativo, y es una
     * garantía global del sistema que esta feature no debe romper. Si el
     * jugador ya gastó el premio en otra sala/juego, la diferencia no
     * cobrada queda registrada en wallets.pending_debt — settlePendingDebt()
     * la descuenta automáticamente del próximo crédito a esa wallet (ver
     * ese método para dónde se invoca).
     *
     * Solo afecta `balance` (retirable); si el premio se había acreditado
     * como bono (BONUS_PRIZE), revertir ese caso queda fuera de este método
     * hasta que haya un caso de uso real.
     *
     * idempotencyKey seguro: mismo patrón que recordPrize()
     * (constraint único en wallet_movements.idempotency_key) — dos llamadas
     * con la misma clave nunca revierten el mismo premio dos veces.
     */
    public function recordPrizeReversal(
        Wallet $wallet,
        float $amount,
        string $gameId,
        string $description,
        string $idempotencyKey,
    ) : WalletMovement {
        $this->assertPositiveAmount($amount);

        $existing = WalletMovement::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        $lockedWallet = $wallet;

        try {
            $movement = DB::transaction(function() use ($wallet, $amount, $gameId, $description, $idempotencyKey, &$lockedWallet) {
                // lockForUpdate() + relectura: sin esto, $wallet->balance es
                // el snapshot que tenía el caller ANTES de abrir esta
                // transacción — si el jugador gasta ese mismo saldo en
                // paralelo (compra de cartones, que sí bloquea la wallet),
                // $debitFromBalance/$debtPortion se calculan sobre un balance
                // obsoleto, pudiendo violar chk_wallets_balance_nonneg (la
                // reversión completa abortaría con una excepción SQL cruda
                // en vez de repartir correctamente balance/pending_debt).
                $locked = Wallet::lockForUpdate()->findOrFail($wallet->id);
                $lockedWallet = $locked;
                $walletId = (string) $locked->id;
                $balanceBefore = (float) $locked->balance;

                // Topar al saldo disponible: chk_wallets_balance_nonneg no
                // permite decrementar por debajo de 0. El resto pasa a deuda.
                $debitFromBalance = min($amount, $balanceBefore);
                $debtPortion = round($amount - $debitFromBalance, 2);

                if ($debitFromBalance > 0) {
                    $this->walletRepository->decrementBalance($walletId, $debitFromBalance);
                }
                if ($debtPortion > 0) {
                    $locked->increment('pending_debt', $debtPortion);
                }
                $locked->refresh();

                return $this->movementRepository->record([
                    'wallet_id' => $walletId,
                    'user_id' => (string) $locked->user_id,
                    'type' => WalletMovement::TYPE_PRIZE_REVERSAL,
                    'status' => WalletMovement::STATUS_COMPLETED,
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => (float) $locked->balance,
                    'description' => $description,
                    'idempotency_key' => $idempotencyKey,
                    'metadata' => [
                        'game_id' => $gameId,
                        'debited_from_balance' => $debitFromBalance,
                        'added_to_pending_debt' => $debtPortion,
                    ],
                ]);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'idempotency_key')) {
                $existingMovement = WalletMovement::where('idempotency_key', $idempotencyKey)->first();
                if ($existingMovement) {
                    return $existingMovement;
                }
            }

            throw $e;
        }

        $this->broadcastWalletUpdated($lockedWallet, WalletMovement::TYPE_PRIZE_REVERSAL, $amount, (string) $movement->id);

        return $movement;
    }

    /**
     * Reembolsa un monto a la wallet de un usuario por cancelación de sala u otro motivo.
     * Registra el movimiento como TYPE_REFUND, separándolo de premios en reportes.
     *
     * @param float $bonusPortion Cuánto de $amount vino originalmente de
     *                            bonus_balance en el gasto que se está reembolsando (ver metadata.bonus_amount
     *                            del movimiento de gasto original, ej. PURCHASE/TRIVIA_ENTRY). Se devuelve
     *                            proporcionalmente a bonus_balance en vez de balance real — de lo contrario
     *                            cancelar una sala convertiría bono en saldo retirable.
     */
    public function recordRefund(
        Wallet $wallet,
        float $amount,
        string $gameId,
        string $description = 'Reembolso',
        float $bonusPortion = 0.0,
        array $extraMetadata = [],
        ?string $idempotencyKey = null,
    ) : WalletMovement {
        $this->assertPositiveAmount($amount);

        [$movement, $created] = DB::transaction(function() use ($wallet, $amount, $gameId, $description, $bonusPortion, $extraMetadata, $idempotencyKey) {
            $walletId = (string) $wallet->id;

            // Serializar reembolsos de una misma wallet y volver a comprobar la
            // clave dentro de la transacción: dos workers pueden reintentar la
            // misma cancelación simultáneamente.
            $lockedWallet = Wallet::lockForUpdate()->findOrFail($walletId);

            if ($idempotencyKey !== null) {
                $existing = WalletMovement::where('idempotency_key', $idempotencyKey)->first();

                if ($existing) {
                    if ((string) $existing->wallet_id !== $walletId
                        || (string) $existing->user_id !== (string) $lockedWallet->user_id
                        || $existing->type !== WalletMovement::TYPE_REFUND
                        || abs((float) $existing->amount - $amount) > 0.00000001
                        || (string) ($existing->metadata['game_id'] ?? '') !== $gameId) {
                        throw new RuntimeException('La clave de idempotencia del reembolso ya existe con datos incompatibles.');
                    }

                    return [$existing, false];
                }
            }

            $balanceBefore = (float) $lockedWallet->balance;
            $bonusBalanceBefore = (float) $lockedWallet->bonus_balance;

            $bonusPortion = min($bonusPortion, $amount);
            $balancePortion = round($amount - $bonusPortion, 2);

            // pending_debt se cobra de cualquier crédito entrante. Solo se
            // aplica a la porción de saldo, no a la porción de bono.
            $balancePortion = $this->settlePendingDebt($lockedWallet, $balancePortion);

            if ($bonusPortion > 0) {
                $this->walletRepository->incrementBonusBalance($walletId, $bonusPortion);
            }
            if ($balancePortion > 0) {
                $this->walletRepository->incrementBalance($walletId, $balancePortion);
            }
            $lockedWallet->refresh();

            $movement = $this->movementRepository->record([
                'wallet_id' => $walletId,
                'user_id' => (string) $lockedWallet->user_id,
                'type' => WalletMovement::TYPE_REFUND,
                'status' => WalletMovement::STATUS_COMPLETED,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => (float) $lockedWallet->balance,
                'description' => $description,
                'idempotency_key' => $idempotencyKey,
                'metadata' => [
                    'game_id' => $gameId,
                    'bonus_amount' => $bonusPortion,
                    'balance_amount' => $balancePortion,
                    'bonus_balance_before' => $bonusBalanceBefore,
                    'bonus_balance_after' => (float) $lockedWallet->bonus_balance,
                    ...$extraMetadata,
                ],
            ]);

            return [$movement, true];
        });

        if ($created) {
            $this->broadcastWalletUpdated($wallet->fresh(), WalletMovement::TYPE_REFUND, $amount, (string) $movement->id);
        }

        return $movement;
    }

    public function recordTriviaEntry(
        Wallet $wallet,
        float $amount,
        string $description = 'Entrada sala Trivia',
        ?string $roomId = null,
        ?string $roomName = null,
        ?string $idempotencyKey = null,
    ) : Wallet {
        $this->assertPositiveAmount($amount);

        // El débito de wallet vive en MySQL y la inscripción de Trivia en
        // PostgreSQL; no hay una transacción distribuida entre ambas bases.
        // La clave estable permite reintentar la inscripción tras un fallo
        // después del débito sin volver a cobrar al jugador.
        if ($idempotencyKey !== null) {
            $existing = WalletMovement::where('idempotency_key', $idempotencyKey)->first();

            if ($existing) {
                if ((string) $existing->wallet_id !== (string) $wallet->id
                    || (string) $existing->user_id !== (string) $wallet->user_id
                    || $existing->type !== WalletMovement::TYPE_TRIVIA_ENTRY
                    || abs((float) $existing->amount - $amount) > 0.00000001) {
                    throw new RuntimeException('La clave de idempotencia de entrada Trivia ya existe con datos incompatibles.');
                }

                return $wallet->fresh(['currency']) ?? $wallet;
            }
        }

        $movementId = null;
        $newMovement = false;

        try {
            $result = DB::transaction(function() use ($wallet, $amount, $description, $roomId, $idempotencyKey, &$movementId, &$newMovement) {
                $walletId = (string) $wallet->id;

                $locked = Wallet::lockForUpdate()->findOrFail($walletId);

                // Revalidar después de tomar el lock MySQL: dos requests no
                // pueden gastar dos veces la misma entrada.
                if ($idempotencyKey !== null) {
                    $existing = WalletMovement::where('idempotency_key', $idempotencyKey)->first();

                    if ($existing) {
                        if ((string) $existing->wallet_id !== $walletId
                            || (string) $existing->user_id !== (string) $locked->user_id
                            || $existing->type !== WalletMovement::TYPE_TRIVIA_ENTRY
                            || abs((float) $existing->amount - $amount) > 0.00000001) {
                            throw new RuntimeException('La clave de idempotencia de entrada Trivia ya existe con datos incompatibles.');
                        }

                        $movementId = (string) $existing->id;

                        return $locked;
                    }
                }

                if (! $locked->hasSufficientPlayableBalance($amount)) {
                    throw WalletDomainException::insufficientBalanceForRoomEntry();
                }

                $balanceBefore = (float) $locked->balance;
                $bonusBalanceBefore = (float) $locked->bonus_balance;

                $split = $this->walletRepository->consumeBonusFirst($walletId, $amount);
                $locked->refresh();

                $movement = $this->movementRepository->record([
                    'wallet_id' => $walletId,
                    'user_id' => (string) $locked->user_id,
                    'type' => WalletMovement::TYPE_TRIVIA_ENTRY,
                    'status' => WalletMovement::STATUS_COMPLETED,
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => (float) $locked->balance,
                    'description' => $description,
                    'idempotency_key' => $idempotencyKey,
                    'metadata' => [
                        'bonus_amount' => $split['bonus_spent'],
                        'balance_amount' => $split['balance_spent'],
                        'bonus_balance_before' => $bonusBalanceBefore,
                        'bonus_balance_after' => (float) $locked->bonus_balance,
                        'room_id' => $roomId,
                    ],
                ]);
                $movementId = (string) $movement->id;
                $newMovement = true;

                return $locked;
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // La restricción UNIQUE de idempotency_key es la última defensa
            // ante dos peticiones simultáneas. La transacción perdedora revierte
            // su débito; reutilizamos el movimiento ganador si coincide.
            if ($idempotencyKey !== null) {
                $existing = WalletMovement::where('idempotency_key', $idempotencyKey)->first();

                if ($existing
                    && (string) $existing->wallet_id === (string) $wallet->id
                    && (string) $existing->user_id === (string) $wallet->user_id
                    && $existing->type === WalletMovement::TYPE_TRIVIA_ENTRY
                    && abs((float) $existing->amount - $amount) <= 0.00000001) {
                    return $wallet->fresh(['currency']) ?? $wallet;
                }
            }

            throw $e;
        }

        // Broadcast fuera de la transacción: el commit MySQL ya ocurrió.
        if ($newMovement && $movementId !== null) {
            $this->broadcastWalletUpdated($result, WalletMovement::TYPE_TRIVIA_ENTRY, $amount, $movementId);

            if ($roomId !== null && $roomName !== null) {
                broadcast(new TriviaEntryPurchased(
                    roomId: $roomId,
                    roomName: $roomName,
                    amount: $amount,
                    currencySymbol: $result->currency?->symbol ?? '',
                ));
            }
        }

        return $result;
    }

    /**
     * Rechazar un depósito PENDING: no toca el saldo, marca REJECTED.
     */
    public function rejectDeposit(WalletMovement $movement, ?string $adminNotes = null) : WalletMovement
    {
        if ($movement->type !== WalletMovement::TYPE_DEPOSIT) {
            throw WalletDomainException::movementNotDeposit();
        }

        $this->guardNotLockedByOther($movement);

        $updated = DB::transaction(function() use ($movement, $adminNotes) {
            $locked = WalletMovement::where('id', $movement->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== WalletMovement::STATUS_PENDING) {
                throw WalletDomainException::rejectionRequiresPendingStatus();
            }

            $locked->update([
                'status' => WalletMovement::STATUS_REJECTED,
                'description' => 'Depósito rechazado',
                'admin_notes' => $adminNotes,
                'processed_by' => (string) Auth::id(),
                'processed_at' => now(),
            ]);

            return $locked->fresh(['wallet', 'wallet.currency']);
        });

        $this->broadcastAndNotifyResult($updated, WalletMovement::STATUS_REJECTED, $adminNotes);

        return $updated;
    }

    /**
     * Rechazar un movimiento PENDING de tipo custom: si is_credit es false el
     * saldo fue descontado al solicitarse y se reintegra aquí (igual que
     * WITHDRAWAL); si es true nunca se tocó el saldo y no hay nada que revertir.
     */
    public function rejectGeneric(WalletMovement $movement, ?string $adminNotes = null) : WalletMovement
    {
        $this->guardNotLockedByOther($movement);

        $updated = DB::transaction(function() use ($movement, $adminNotes) {
            $locked = WalletMovement::where('id', $movement->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== WalletMovement::STATUS_PENDING) {
                throw WalletDomainException::rejectionRequiresPendingStatus();
            }

            if (! $locked->isCredit()) {
                // pending_debt se cobra de cualquier crédito entrante, incluido
                // el reintegro de un débito rechazado — ver recordRefund().
                $netAmount = $this->settlePendingDebt($locked->wallet, (float) $locked->amount);
                if ($netAmount > 0) {
                    $this->walletRepository->incrementBalance((string) $locked->wallet_id, $netAmount);
                }
                $locked->wallet->refresh();
            }

            $locked->update([
                'status' => WalletMovement::STATUS_REJECTED,
                'admin_notes' => $adminNotes,
                'processed_by' => (string) Auth::id(),
                'processed_at' => now(),
            ]);

            return $locked->fresh(['wallet', 'wallet.currency']);
        });

        $this->broadcastAndNotifyResult($updated, WalletMovement::STATUS_REJECTED, $adminNotes);

        return $updated;
    }

    /**
     * Rechazar un retiro PENDING: devuelve el monto al saldo y marca REJECTED.
     *
     * status revalidado DENTRO de la transacción, bajo lockForUpdate() sobre
     * el movimiento — antes se leía sin lock antes de abrir la transacción,
     * así que dos rechazos concurrentes del mismo retiro podían ambos pasar
     * el check y ambos reintegrar el monto (doble crédito).
     */
    public function rejectWithdrawal(WalletMovement $movement, ?string $adminNotes = null) : WalletMovement
    {
        if ($movement->type !== WalletMovement::TYPE_WITHDRAWAL) {
            throw WalletDomainException::movementNotWithdrawal();
        }

        $this->guardNotLockedByOther($movement);

        $updated = DB::transaction(function() use ($movement, $adminNotes) {
            $locked = WalletMovement::where('id', $movement->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== WalletMovement::STATUS_PENDING) {
                throw WalletDomainException::rejectionRequiresPendingStatus();
            }

            // pending_debt se cobra de cualquier crédito entrante, incluido
            // el reintegro de un retiro rechazado — ver recordRefund() para
            // el porqué.
            $netAmount = $this->settlePendingDebt($locked->wallet, (float) $locked->amount);
            if ($netAmount > 0) {
                $this->walletRepository->incrementBalance((string) $locked->wallet_id, $netAmount);
            }
            $locked->wallet->refresh();

            $locked->update([
                'status' => WalletMovement::STATUS_REJECTED,
                'description' => 'Retiro rechazado — saldo reintegrado',
                'admin_notes' => $adminNotes,
                'processed_by' => (string) Auth::id(),
                'processed_at' => now(),
            ]);

            return $locked->fresh(['wallet', 'wallet.currency']);
        });

        // El retiro rechazado ya no cuenta como PENDING/COMPLETED — sin
        // invalidar, withdrawalSums() lo sigue sumando hasta medianoche y
        // bloquea al usuario contra un límite que ya no debería aplicar.
        $this->walletLimitService->invalidateWithdrawalSums((string) $updated->wallet_id);

        $this->broadcastAndNotifyResult($updated, WalletMovement::STATUS_REJECTED, $adminNotes);

        return $updated;
    }

    /**
     * Registrar solicitud de depósito con comprobante (STATUS_PENDING, sin tocar el saldo).
     * Recibe la wallet ya resuelta y verificada desde el controller.
     */
    public function requestDeposit(
        Wallet $wallet,
        float $amount,
        string $receipt,
        ?string $receiptHash = null,
        ?string $reference = null,
        ?string $notes = null,
    ) : Wallet {
        if ($wallet->isBlocked()) {
            throw WalletDomainException::walletBlocked();
        }

        $kycLevel = (int) ($wallet->user?->kyc_level ?? 0);
        $this->walletLimitService->assertDepositWithinLimits($wallet, $amount, $kycLevel);

        // Checks antifraude
        $this->fraudService->checkDepositVelocity((string) $wallet->user_id);
        $this->fraudService->checkDoubleSubmit((string) $wallet->id, $amount, WalletMovement::TYPE_DEPOSIT);
        if ($reference) {
            $this->fraudService->checkDuplicateReference((string) $wallet->id, $reference);
        }

        if ($this->getPendingOperation((string) $wallet->id, WalletMovement::TYPE_DEPOSIT)) {
            throw WalletDomainException::depositAlreadyPending();
        }

        $movement = $this->movementRepository->record([
            'wallet_id' => (string) $wallet->id,
            'user_id' => (string) $wallet->user_id,
            'type' => WalletMovement::TYPE_DEPOSIT,
            'status' => WalletMovement::STATUS_PENDING,
            'amount' => $amount,
            'balance_before' => (float) $wallet->balance,
            'balance_after' => (float) $wallet->balance,
            'description' => $notes ?? 'Depósito pendiente de confirmación',
            'reference_id' => $reference,
            'receipt_path' => $receipt,
            'receipt_hash' => $receiptHash,
        ]);

        // Sin esto, checkDepositVelocity() sigue viendo el conteo cacheado
        // ANTES de este depósito el resto del día (ver invalidateVelocity()).
        $this->fraudService->invalidateVelocity((string) $wallet->user_id, WalletMovement::TYPE_DEPOSIT);

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
        Wallet $wallet,
        float $amount,
        string $directoryId,
        string $directoryType,
        ?string $notes = null,
        ?string $withdrawalPin = null,
    ) : Wallet {
        $withdrawalMovement = null;
        $userId = (string) $wallet->user_id;

        // PIN de retiro: verificado ANTES que cualquier otro check —
        // ninguna validación de negocio (velocity, límites, saldo) debe
        // ejecutarse hasta confirmar que quien pide el retiro conoce la
        // segunda credencial, incluida la wallet bloqueada (no revelar ese
        // estado a quien no probó el PIN).
        $this->assertWithdrawalPin($wallet, $withdrawalPin);

        // Checks antifraude y límites ANTES de abrir la transacción
        $this->fraudService->checkWithdrawalVelocity($userId);
        $this->fraudService->checkDoubleSubmit((string) $wallet->id, $amount, WalletMovement::TYPE_WITHDRAWAL);
        $kycLevel = (int) ($wallet->user?->kyc_level ?? 0);
        $this->walletLimitService->assertWithinLimits($wallet, $amount, $kycLevel);

        $wallet = DB::transaction(function() use ($wallet, $amount, $directoryId, $directoryType, $notes, &$withdrawalMovement) {
            $walletId = (string) $wallet->id;

            if ($wallet->isBlocked()) {
                throw WalletDomainException::walletBlocked();
            }

            if ($this->getPendingOperation($walletId, WalletMovement::TYPE_WITHDRAWAL)) {
                throw WalletDomainException::withdrawalAlreadyPending();
            }

            if (! $wallet->hasSufficientBalance($amount)) {
                throw WalletDomainException::insufficientBalanceForWithdrawal();
            }

            $balanceBefore = (float) $wallet->balance;

            $this->walletRepository->decrementBalance($walletId, $amount);
            $wallet->refresh();

            $withdrawalMovement = $this->movementRepository->record([
                'wallet_id' => (string) $wallet->id,
                'user_id' => (string) $wallet->user_id,
                'type' => WalletMovement::TYPE_WITHDRAWAL,
                'status' => WalletMovement::STATUS_PENDING,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => (float) $wallet->balance,
                'description' => $notes ?? 'Retiro pendiente de procesamiento',
                'metadata' => [
                    'directory_id' => $directoryId,
                    'directory_type' => $directoryType,
                ],
            ]);

            return $wallet;
        });

        // Fuera de la transacción (ya hizo commit): sin esto, un segundo
        // retiro dentro de la misma ventana de caché de withdrawalSums()
        // vería las sumas previas a este retiro y podría exceder los
        // límites diario/semanal/mensual (ver invalidateWithdrawalSums()).
        $this->walletLimitService->invalidateWithdrawalSums((string) $wallet->id);
        $this->fraudService->invalidateVelocity((string) $wallet->user_id, WalletMovement::TYPE_WITHDRAWAL);

        $this->broadcastWalletUpdated($wallet, WalletMovement::TYPE_WITHDRAWAL, $amount);
        $this->broadcastAdminWalletMovement($withdrawalMovement, $wallet, WalletMovement::TYPE_WITHDRAWAL, $amount);

        return $wallet;
    }

    /**
     * Fija o cambia el PIN de retiro del usuario. El caller (controller)
     * es responsable de reautenticar con current_password ANTES de llamar
     * a este método — este método asume que ya se verificó que quien pide
     * el cambio es el dueño real de la cuenta, igual que
     * ProfileController::changePassword() delega esa verificación a la
     * regla de validación 'current_password' antes de invocar el service.
     *
     * $pin ya debe venir validado como 6 dígitos (regla 'digits:6' en el
     * FormRequest) — este método no revalida el formato, solo hashea y
     * persiste.
     */
    public function setWithdrawalPin(User $user, string $pin) : void
    {
        $user->forceFill([
            'withdrawal_pin_hash' => Hash::make($pin),
            'withdrawal_pin_set_at' => now(),
        ])->save();

        // Limpiar cualquier bloqueo previo: un cambio de PIN reautenticado
        // con current_password es una acción legítima del dueño de la
        // cuenta, no debe quedar penalizada por intentos fallidos con el
        // PIN anterior.
        $this->fraudService->clearWithdrawalPinAttempts((string) $user->id);

        Log::info('[WalletService] PIN de retiro actualizado', ['user_id' => (string) $user->id]);

        $this->notificationService->createWithdrawalPinChangedNotification((string) $user->id);
    }

    /**
     * Libera la asignación ("Liberar caso"). El propio dueño de la
     * asignación o un superadmin pueden liberar; cualquier otro admin recibe
     * 409. Liberar un movimiento sin asignar (o cuyo lock ya expiró) es un
     * no-op seguro.
     */
    public function unassignMovement(WalletMovement $movement) : WalletMovement
    {
        $actor = Auth::user();

        if (
            $movement->assigned_to_user_id
            && (string) $movement->assigned_to_user_id !== (string) $actor->id
            && ! $actor->hasRole('superadmin')
        ) {
            throw WalletDomainException::cannotUnassignOthersMovement(
                $movement->assignedTo?->first_name ?? 'otro admin',
            );
        }

        $movement->forceFill([
            'assigned_to_user_id' => null,
            'assigned_at' => null,
        ])->save();

        $this->broadcastMovementAssigned($movement->fresh(), $actor, assigned: false);

        return $movement->fresh(['wallet', 'wallet.currency', 'assignedTo']);
    }

    /**
     * Registrar solicitud de retiro.
     */
    public function withdraw(string $walletId, float $amount) : Wallet
    {
        $movementId = null;

        $wallet = DB::transaction(function() use ($walletId, $amount, &$movementId) {
            $wallet = $this->findOwnedWallet($walletId);

            if ($wallet->isBlocked()) {
                throw WalletDomainException::walletBlocked();
            }

            if (! $wallet->hasSufficientBalance($amount)) {
                throw WalletDomainException::insufficientBalanceForWithdrawal();
            }

            $balanceBefore = (float) $wallet->balance;

            $this->walletRepository->decrementBalance($walletId, $amount);
            $wallet->refresh();

            $movement = $this->movementRepository->record([
                'wallet_id' => (string) $wallet->id,
                'user_id' => (string) $wallet->user_id,
                'type' => WalletMovement::TYPE_WITHDRAWAL,
                'status' => WalletMovement::STATUS_PENDING,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => (float) $wallet->balance,
                'description' => 'Retiro manual',
            ]);
            $movementId = (string) $movement->id;

            return $wallet;
        });

        // Broadcast FUERA de la transacción: garantiza que el commit ya ocurrió
        $this->broadcastWalletUpdated($wallet, WalletMovement::TYPE_WITHDRAWAL, $amount, $movementId);

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
    /**
     * Defensa de última línea en cada método de crédito/débito del núcleo
     * (recordPrize, recordRefund, recordCryptoDeposit, recordMarketplaceCredit,
     * recordCardPurchase, recordTriviaEntry, recordHoldCapture): ninguno de
     * esos métodos validaba $amount > 0 por sí mismo, confiando en que el
     * caller (un juego, marketplace, el watcher de cripto) siempre calcula
     * bien el monto. Un monto negativo/cero coleado hasta aquí se registra
     * igual como WalletMovement sin que nadie lo note (ej. recordPrize con
     * amount negativo: settlePendingDebt() calcula netAmount<=0 y el
     * incrementBalance() correspondiente simplemente no se ejecuta,
     * dejando un movimiento fantasma que rompe la cuadratura del ledger).
     */
    private function assertPositiveAmount(float $amount) : void
    {
        if ($amount <= 0) {
            throw WalletDomainException::invalidMovementAmount($amount);
        }
    }

    /**
     * Valida el PIN de retiro antes de dejar avanzar requestWithdraw().
     *
     * Orden de checks deliberado:
     *   1. Lockout PRIMERO (antes de tocar Hash::check()) — un usuario
     *      bloqueado no debe poder seguir probando PINs aunque adivine uno
     *      correcto a mitad del bloqueo.
     *   2. Si nunca fijó un PIN, error distinto (withdrawalPinNotSet) que no
     *      cuenta como intento fallido — no es un ataque, es una cuenta sin
     *      configurar.
     *   3. PIN vacío en el request: error distinto de "incorrecto" —evita
     *      filtrar por el mensaje si el PIN existe o no cuando el campo
     *      simplemente no llegó.
     *   4. Hash::check() real. Solo AQUÍ se cuenta como intento (fallido o
     *      limpia el contador si acierta) — los pasos 2-3 no deben poder
     *      usarse para acumular intentos sin nunca tocar el hash real.
     */
    private function assertWithdrawalPin(Wallet $wallet, ?string $withdrawalPin) : void
    {
        $user = $wallet->relationLoaded('user') ? $wallet->user : $wallet->user()->first();
        $userId = (string) $wallet->user_id;

        if (! $user || ! $user->hasWithdrawalPin()) {
            throw WalletDomainException::withdrawalPinNotSet();
        }

        $this->fraudService->checkWithdrawalPinLockout($userId);

        if (empty($withdrawalPin)) {
            throw WalletDomainException::withdrawalPinRequired();
        }

        if (! Hash::check($withdrawalPin, $user->withdrawal_pin_hash)) {
            $remaining = $this->fraudService->recordWithdrawalPinFailure($userId);

            throw WalletDomainException::withdrawalPinInvalid($remaining);
        }

        $this->fraudService->clearWithdrawalPinAttempts($userId);
    }

    /**
     * Notifica a admins/superadmins/tesorería vía WebSocket + DB cuando llega un movimiento pendiente.
     */
    private function broadcastAdminWalletMovement(?WalletMovement $movement, Wallet $wallet, string $type, float $amount) : void
    {
        $user = $wallet->user ?? User::find($wallet->user_id);
        $currency = $wallet->currency;

        $movementId = $movement ? (string) $movement->id : '';
        $userName = $user
            ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->username ?? $user->email)
            : 'Usuario';

        // 1. Broadcast WebSocket al canal de admins (best-effort)
        try {
            broadcast(new WalletMovementRequested(
                movementId: $movementId,
                userId: (string) $wallet->user_id,
                userName: $userName,
                movementType: $type,
                amount: $amount,
                currencySymbol: $currency?->symbol ?? '',
                currencyIso: $currency?->iso_code ?? '',
            ));
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[WalletService] broadcastAdminWalletMovement falló (WebSocket no disponible): ' . $e->getMessage());
        }

        // 2. Notificación persistente en DB para cada admin/superadmin/tesorería
        $admins = \App\Models\User::whereHas(
            'roles',
            fn ($q) => $q->whereIn('name', ['admin', 'superadmin', 'treasurer']),
        )->get();

        $typeLabel = $type === WalletMovement::TYPE_DEPOSIT ? 'depósito' : 'retiro';
        $symbol = $currency?->symbol ?? '';

        foreach ($admins as $admin) {
            $this->notificationService->create(
                userId: (string) $admin->id,
                type: 'WALLET_ADMIN_ALERT',
                title: $type === WalletMovement::TYPE_DEPOSIT
                            ? '💰 Nuevo depósito pendiente'
                            : '💸 Nuevo retiro pendiente',
                message: "{$userName} solicitó un {$typeLabel} de {$symbol}{$amount}. Requiere tu aprobación.",
                data: [
                    'movement_id' => $movementId,
                    'movement_type' => $type,
                    'amount' => $amount,
                    'currency_symbol' => $symbol,
                    'user_id' => (string) $wallet->user_id,
                    'user_name' => $userName,
                    'url' => $movementId ? "/wallet-movements?movement={$movementId}" : '/wallet-movements',
                ],
            );
        }
    }

    /**
     * Broadcast WebSocket + notificación persistente al usuario tras aprobar/rechazar.
     */
    private function broadcastAndNotifyResult(WalletMovement $movement, string $status, ?string $adminNotes) : void
    {
        $wallet = $movement->wallet;
        $currencySymbol = $wallet?->currency?->symbol ?? '';
        $userId = (string) $movement->user_id;
        $amount = (float) $movement->amount;

        // WebSocket: notifica al canal privado del usuario (best-effort — no bloquea si Reverb está caído)
        try {
            broadcast(new WalletUpdated(
                userId: $userId,
                walletId: (string) $movement->wallet_id,
                balance: (float) ($wallet?->balance ?? 0),
                currencySymbol: $currencySymbol,
                movementType: $movement->type,
                amount: $amount,
                movementStatus: $status,
                movementId: (string) $movement->id,
            ));
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[WalletService] broadcastAndNotifyResult falló (WebSocket no disponible): ' . $e->getMessage());
        }

        // Notificación persistente en tabla notifications
        $movementId = (string) $movement->id;

        if ($movement->type === WalletMovement::TYPE_DEPOSIT) {
            $this->notificationService->createDepositResultNotification(
                userId: $userId,
                status: $status,
                amount: $amount,
                currencySymbol: $currencySymbol,
                adminNotes: $adminNotes,
                movementId: $movementId,
            );
        } elseif ($movement->type === WalletMovement::TYPE_WITHDRAWAL) {
            $this->notificationService->createWithdrawalResultNotification(
                userId: $userId,
                status: $status,
                amount: $amount,
                currencySymbol: $currencySymbol,
                adminNotes: $adminNotes,
                movementId: $movementId,
            );
        } else {
            $type = \App\Models\MovementType::catalog()->get($movement->type);
            $this->notificationService->createMovementResultNotification(
                userId: $userId,
                movementType: $movement->type,
                status: $status,
                amount: $amount,
                currencySymbol: $currencySymbol,
                typeLabel: $type?->label ?? $movement->type,
                adminNotes: $adminNotes,
                movementId: $movementId,
            );
        }

        // Notifica a los admins que el pendiente fue procesado para que
        // actualicen su contador en tiempo real sin recargar la página.
        try {
            $pendingCount = WalletMovement::where('status', WalletMovement::STATUS_PENDING)->count();
            broadcast(new \App\Domains\Wallet\Events\WalletMovementProcessed(
                movementId: (string) $movement->id,
                movementType: $movement->type,
                status: $status,
                pendingCount: $pendingCount,
            ));
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[WalletService] WalletMovementProcessed broadcast falló: ' . $e->getMessage());
        }
    }

    /**
     * Broadcast best-effort (nunca bloquea la operación principal) de que un
     * admin tomó o liberó un caso — mismo patrón try/catch que el resto de
     * los broadcasts de este servicio (ver broadcastAndNotifyResult()).
     */
    private function broadcastMovementAssigned(WalletMovement $movement, User $actor, bool $assigned) : void
    {
        try {
            broadcast(new WalletMovementAssigned(
                movementId: (string) $movement->id,
                assignedToUserId: $assigned ? (string) $actor->id : null,
                assignedToName: $assigned ? trim("{$actor->first_name} {$actor->last_name}") : null,
            ));
        } catch (Throwable $e) {
            Log::warning('[WalletService] broadcastMovementAssigned falló (WebSocket no disponible): ' . $e->getMessage());
        }
    }

    /**
     * Emite el evento WebSocket con el nuevo saldo.
     */
    private function broadcastWalletUpdated(Wallet $wallet, string $type, float $amount, ?string $movementId = null, ?string $reason = null) : void
    {
        try {
            broadcast(new WalletUpdated(
                userId: (string) $wallet->user_id,
                walletId: (string) $wallet->id,
                balance: (float) $wallet->balance,
                currencySymbol: $wallet->currency->symbol ?? '',
                movementType: $type,
                amount: $amount,
            ));
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[WalletService] broadcastWalletUpdated falló (WebSocket no disponible): ' . $e->getMessage());
        }

        // Notificación persistente en queue — no bloquea el hilo del caller
        // (crítico en cancelaciones masivas con N reembolsos consecutivos)
        DispatchWalletNotificationJob::dispatch(
            userId: (string) $wallet->user_id,
            balance: (float) $wallet->balance,
            currencySymbol: $wallet->currency->symbol ?? '',
            movementType: $type,
            amount: $amount,
            movementId: $movementId,
            reason: $reason,
        );
    }

    private function findOwnedWallet(string $walletId) : Wallet
    {
        $wallet = Wallet::with(['currency', 'user'])->lockForUpdate()->findOrFail($walletId);

        if ((string) $wallet->user_id !== (string) Auth::id()) {
            abort(403, 'No autorizado.');
        }

        return $wallet;
    }

    private function getBinanceDirectories(string $userId) : array
    {
        return BinanceAccount::where('user_id', $userId)
            ->where('status', 'ACTIVE')
            ->get()
            ->map(fn ($b) => [
                'id' => (string) $b->id,
                'type' => 'binance',
                'label' => $b->account_type_description,
                'detail' => $b->data_value,
                'holder' => null,
            ])
            ->values()
            ->toArray();
    }

    private function getPagoMovilDirectories(string $userId) : array
    {
        return PagoMovil::where('user_id', $userId)
            ->where('status', 'ACTIVE')
            ->with(['bank', 'documentType'])
            ->get()
            ->map(fn ($pm) => [
                'id' => (string) $pm->id,
                'type' => 'pago_movil',
                'label' => $pm->bank->name ?? 'Banco',
                'detail' => ($pm->documentType->code ?? 'CI') . '-' . $pm->document_number . ' | ' . $pm->phone,
                'holder' => $pm->holder_name,
            ])
            ->values()
            ->toArray();
    }

    private function getPixDirectories(string $userId) : array
    {
        return PixKey::where('user_id', $userId)
            ->where('status', 'ACTIVE')
            ->with('bank')
            ->get()
            ->map(fn ($pix) => [
                'id' => (string) $pix->id,
                'type' => 'pix',
                'label' => $pix->bank->name ?? 'Banco',
                'detail' => "{$pix->getTypeDisplayName()}: {$pix->formatted_key}",
                'holder' => $pix->holder_name,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Guard reusado por assignMovement() y por cada approve/reject: si
     * el movimiento está asignado a alguien distinto del actor autenticado,
     * el lock no ha expirado (assigned_at dentro de ASSIGNMENT_LOCK_MINUTES)
     * y el actor no es superadmin, bloquea con 409 — hace que el lock de
     * asignación sea real y no decorativo. Sin esto, "Tomar caso" en la UI
     * sería solo un badge informativo que cualquiera podría ignorar.
     */
    private function guardNotLockedByOther(WalletMovement $movement) : void
    {
        if (! $movement->assigned_to_user_id) {
            return;
        }

        $actor = Auth::user();

        if ((string) $movement->assigned_to_user_id === (string) $actor?->id) {
            return;
        }

        if ($actor?->hasRole('superadmin')) {
            return;
        }

        $lockExpired = ! $movement->assigned_at
            || $movement->assigned_at->lt(now()->subMinutes(self::ASSIGNMENT_LOCK_MINUTES));

        if ($lockExpired) {
            return;
        }

        throw WalletDomainException::movementLockedByAnotherAdmin(
            $movement->assignedTo?->first_name ?? 'otro admin',
        );
    }

    /**
     * Descuenta pending_debt del monto de un crédito entrante ANTES de
     * incrementar balance — así el jugador nunca "ve" el saldo nuevo sin
     * que la deuda de una reversión de premio anterior ya se haya cobrado.
     *
     * @return float monto neto a acreditar tras descontar la deuda (puede
     *               ser menor que $creditAmount, o 0 si la deuda lo consume todo)
     */
    private function settlePendingDebt(Wallet $wallet, float $creditAmount) : float
    {
        $debt = (float) $wallet->pending_debt;
        if ($debt <= 0) {
            return $creditAmount;
        }

        $settled = min($debt, $creditAmount);
        $wallet->decrement('pending_debt', $settled);
        $wallet->refresh();

        return round($creditAmount - $settled, 2);
    }
}
