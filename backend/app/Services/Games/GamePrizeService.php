<?php

declare(strict_types=1);

namespace App\Services\Games;

use App\Services\WalletService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fuente de verdad para el pago de premios en cualquier juego de la plataforma.
 *
 * Garantías:
 *  – Idempotente: el mismo $prizeRecord nunca se paga dos veces, aunque dos
 *    workers o procesos llamen a payout() simultáneamente (lockForUpdate + FSM).
 *  – Atómico: la transición de estado (waiting → processing) ocurre en una
 *    transacción corta; el pago a la wallet ocurre FUERA de esa transacción,
 *    evitando que un broadcast de saldo quede dentro de un commit ajeno.
 *  – Recuperable: si el pago falla tras actualizar la wallet, el registro queda
 *    en 'processing' con un payment sin 'completed'; el reintento detecta la
 *    situación y sólo repara el estado sin volver a tocar la wallet.
 *
 * Contrato del modelo $prizeRecord:
 *  El modelo debe implementar GamePrizeContract o exponer los métodos y campos:
 *    - prizeStatusField(): string              → nombre de la columna FSM
 *    - isWaiting(): bool
 *    - isProcessing(): bool
 *    - markAsProcessing(): void
 *    - markAsClaimed(): void
 *    - getPaymentRelation(): string            → nombre de la relación a payments
 *    - getUserId(): string                     → ID del ganador
 *    - getCurrencyId(): string
 *    - getLocalAmount(): float
 *    - getGameId(): string                     → ID de la sala/room para descripción
 *    - getGameSlug(): string                   → ej. 'bingo', 'trivia'
 *    - buildPaymentAttributes(string $movementId, string $status, ?string $reason): array
 */
final class GamePrizeService
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    /**
     * Paga el premio al ganador de forma idempotente y atómica.
     *
     * @param  Model&GamePrizeContract  $prizeRecord  El registro del premio (BingoCardPrize, etc.)
     * @param  string                   $description   Texto que aparecerá en el movimiento de wallet
     */
    public function payout(GamePrizeContract $prizeRecord, string $description): void
    {
        $locked = DB::transaction(function () use ($prizeRecord): ?GamePrizeContract {
            /** @var GamePrizeContract $row */
            $row = $prizeRecord::where('id', $prizeRecord->getKey())
                ->lockForUpdate()
                ->first();

            if (!$row) {
                return null;
            }

            if ($row->isWaiting()) {
                $row->markAsProcessing();
                return $row;
            }

            if ($row->isProcessing()) {
                // Reintento: si ya existe un pago completado la wallet ya fue
                // acreditada — sólo reparar el estado sin re-acreditar.
                if ($row->hasCompletedPayment()) {
                    Log::warning('[GamePrizeService] Pago completado ya existente en processing — reparando estado', [
                        'game'     => $row->getGameSlug(),
                        'prize_id' => $row->getKey(),
                    ]);
                    $row->markAsClaimed();
                    return null;
                }
                // Sin pago completado todavía: reintentar.
                return $row;
            }

            // 'claimed', 'cancelled', 'suspended' — nada que hacer.
            return null;
        });

        if ($locked === null) {
            return;
        }

        $userId   = $locked->getUserId();
        $gameSlug = $locked->getGameSlug();

        try {
            $wallet = $this->walletService->findActiveWallet($userId, $locked->getCurrencyId());

            if (!$wallet) {
                Log::warning('[GamePrizeService] Ganador sin wallet activa', [
                    'game'        => $gameSlug,
                    'prize_id'    => $locked->getKey(),
                    'user_id'     => $userId,
                    'currency_id' => $locked->getCurrencyId(),
                ]);
                $this->recordPayment($locked, null, 'failed', 'El ganador no tiene billetera activa en la moneda del premio.');
                return;
            }

            $amount   = $locked->getLocalAmount();
            $movement = $this->walletService->recordPrize(
                wallet:      $wallet,
                amount:      $amount,
                gameId:      $locked->getGameId(),
                description: $description,
            );

            DB::transaction(function () use ($locked, $movement) {
                $this->recordPayment($locked, (string) $movement->id, 'completed', null);
                $locked->markAsClaimed();
            });

            Log::info('[GamePrizeService] Premio acreditado', [
                'game'        => $gameSlug,
                'prize_id'    => $locked->getKey(),
                'user_id'     => $userId,
                'wallet_id'   => $wallet->id,
                'amount'      => $amount,
                'movement_id' => $movement->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('[GamePrizeService] Falló el pago — prize_status queda en processing', [
                'game'     => $gameSlug,
                'prize_id' => $locked->getKey(),
                'user_id'  => $userId,
                'error'    => $e->getMessage(),
            ]);
            $this->recordPayment($locked, null, 'failed', $e->getMessage());
        }
    }

    private function recordPayment(
        GamePrizeContract $prize,
        ?string $movementId,
        string $status,
        ?string $reason
    ): void {
        $attrs = $prize->buildPaymentAttributes($movementId, $status, $reason);
        $prize->createPayment($attrs);
    }
}
