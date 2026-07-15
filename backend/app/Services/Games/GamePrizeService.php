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
 *    workers o procesos llamen a payout() simultáneamente (lockForUpdate + FSM
 *    + idempotencyKey en WalletService::recordPrize()).
 *  – Atómico: la transición de estado (waiting → processing) ocurre en una
 *    transacción corta; el pago a la wallet ocurre FUERA de esa transacción,
 *    evitando que un broadcast de saldo quede dentro de un commit ajeno.
 *    NOTA: si $prizeRecord vive en una conexión distinta a wallet_movements
 *    (ej. TriviaParticipant en pgsql_trivia vs wallet_movements en mysql),
 *    DB::transaction() y lockForUpdate() actúan sobre motores de BD separados
 *    — no hay atomicidad real entre ambos. La protección efectiva contra doble
 *    pago en ese caso es el idempotencyKey (constraint único en wallet_movements),
 *    no el lock. Para modelos mono-BD (ej. BingoCardPrize + wallets en mysql)
 *    el lock sí es atómico con el pago y el idempotencyKey es una capa extra.
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

            if ($row->isWaiting() || $row->isProcessing()) {
                // Reparar antes de (re)intentar: si ya existe un pago completado
                // la wallet ya fue acreditada — esto cubre tanto el camino normal
                // (waiting→processing) como un registro que por algún motivo volvió
                // a 'waiting'/quedó en 'processing' con el pago ya resuelto.
                if ($row->hasCompletedPayment()) {
                    Log::warning('[GamePrizeService] Pago completado ya existente — reparando estado', [
                        'game'     => $row->getGameSlug(),
                        'prize_id' => $row->getKey(),
                    ]);
                    $row->markAsClaimed();
                    return null;
                }

                $row->markAsProcessing();
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
                Log::warning('[GamePrizeService] Ganador sin wallet activa — suspendiendo premio para revisión manual', [
                    'game'        => $gameSlug,
                    'prize_id'    => $locked->getKey(),
                    'user_id'     => $userId,
                    'currency_id' => $locked->getCurrencyId(),
                ]);
                // Wallet inexistente es un error permanente (no transitorio): transicionar
                // a 'suspended' para que el retry cron no lo intente indefinidamente y
                // un operador pueda revisarlo manualmente.
                DB::transaction(function () use ($locked) {
                    $this->recordPayment($locked, null, 'failed', 'El ganador no tiene billetera activa en la moneda del premio.');
                    $locked->prize_status = 'suspended';
                    $locked->save();
                });
                return;
            }

            $amount   = $locked->getLocalAmount();

            // IMPORTANTE: recordPrize() y el registro del payment + markAsClaimed()
            // NO pueden estar en la misma transacción porque recordPrize() modifica
            // la wallet (balance externo). Separar las dos operaciones expone una
            // ventana donde el worker muere entre ambas, pero eso se cubre con el
            // path de reintento hasCompletedPayment() en la transacción 1 de arriba.
            //
            // El riesgo de doble pago existe si dos workers concurrentes pasan la
            // transacción 1 (markAsProcessing) y ambos llaman recordPrize() antes de
            // que cualquiera commitee la transacción 2. Para mitigarlo, la transacción 2
            // re-verifica hasCompletedPayment() con lockForUpdate antes de creditar.
            $movement = null;
            DB::transaction(function () use ($locked, $wallet, $amount, $description, $userId, &$movement) {
                // Re-verificar bajo lock que nadie más completó el pago mientras esperábamos.
                /** @var \App\Services\Games\GamePrizeContract $row */
                $row = $locked::where('id', $locked->getKey())->lockForUpdate()->first();
                if (!$row || $row->hasCompletedPayment()) {
                    return; // ya pagado por un worker concurrente — no continuar
                }
                // Clave de idempotencia POR REGISTRO DE PREMIO (no por sala+usuario):
                // usar getKey() (card_prize_id / trivia_participant_id) garantiza que:
                // (a) reintentos del mismo premio nunca generan un segundo movimiento,
                // (b) dos ganadores distintos en la misma sala NO comparten la clave,
                // (c) un mismo usuario que gana en dos rondas diferentes tiene claves distintas.
                $movement = $this->walletService->recordPrize(
                    wallet:         $wallet,
                    amount:         $amount,
                    gameId:         $locked->getGameId(),
                    description:    $description,
                    idempotencyKey: sprintf('%s_prize:%s', $locked->getGameSlug(), $locked->getKey()),
                );
                $this->recordPayment($locked, (string) $movement->id, 'completed', null);
                $locked->markAsClaimed();
            });

            if ($movement === null) {
                // Otro worker completó el pago concurrentemente — nada más que hacer.
                return;
            }

            Log::info('[GamePrizeService] Premio acreditado', [
                'game'        => $gameSlug,
                'prize_id'    => $locked->getKey(),
                'user_id'     => $userId,
                'wallet_id'   => $wallet->id,
                'amount'      => $amount,
                'movement_id' => $movement->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('[GamePrizeService] Falló el pago — prize_status queda en processing para reintento', [
                'game'     => $gameSlug,
                'prize_id' => $locked->getKey(),
                'user_id'  => $userId,
                'error'    => $e->getMessage(),
            ]);
            try {
                $this->recordPayment($locked, null, 'failed', $e->getMessage());
            } catch (\Throwable $inner) {
                Log::error('[GamePrizeService] No se pudo registrar el payment fallido', [
                    'prize_id' => $locked->getKey(),
                    'error'    => $inner->getMessage(),
                ]);
            }
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
