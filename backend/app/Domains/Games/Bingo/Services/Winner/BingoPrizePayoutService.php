<?php

namespace App\Domains\Games\Bingo\Services\Winner;

use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrizePayment;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Acredita el monto de un BingoCardPrize a la billetera del ganador.
 *
 * Se invoca siempre DESPUÉS de que BingoDrawService::claimCardWin() confirma
 * el premio (fuera de esa transacción): el registro del premio nunca debe
 * revertirse porque falle el pago — si la wallet no se puede acreditar, el
 * premio queda en prize_status = 'waiting'/'processing' para reintentar, y
 * se deja un BingoCardPrizePayment con payment_status = 'failed' para
 * trazabilidad.
 *
 * Máquina de estados de prize_status relevante aquí:
 *   waiting  --(reserva bajo lock)-->  processing  --(pago ok)-->  claimed
 *                                          |
 *                                          '--(pago falló a mitad)--> se queda en
 *                                              processing; el reintento verifica
 *                                              si ya hay un payment 'completed'
 *                                              antes de volver a tocar la wallet.
 */
final class BingoPrizePayoutService
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function payout(BingoCardPrize $cardPrize): void
    {
        // Reserva atómica bajo lock: transiciona waiting → processing dentro de
        // una transacción corta. lockForUpdate() serializa llamadas concurrentes
        // a payout() sobre el mismo premio (p.ej. claimCardWin() y un reintento
        // del cron solapados) — la que pierde la carrera ve un prize_status
        // distinto de 'waiting' al obtener el lock y no reintenta nada.
        // recordPrize() (que toca la wallet) corre después, fuera de esta
        // transacción, para no atrapar su broadcast de saldo dentro de un
        // commit ajeno.
        $locked = DB::transaction(function () use ($cardPrize) {
            $row = BingoCardPrize::where('id', $cardPrize->id)->lockForUpdate()->first();

            if (!$row) {
                return null;
            }

            if ($row->isWaiting()) {
                $row->markAsProcessing();
                return $row;
            }

            if ($row->isProcessing()) {
                // Reintento sobre un premio que quedó a medias en una corrida
                // anterior: si ya existe un pago 'completed', la wallet ya fue
                // acreditada — solo reparar el estado sin volver a tocarla.
                if ($row->payments()->where('payment_status', 'completed')->exists()) {
                    Log::warning('[BingoPrizePayoutService] Pago completado ya existente para un premio en processing — reparando estado sin re-acreditar', [
                        'card_prize_id' => $row->id,
                    ]);
                    $row->markAsClaimed();
                    return null;
                }
                // Ningún pago completado todavía: reintentar el pago sobre este
                // mismo registro, ya en 'processing'.
                return $row;
            }

            // 'claimed', 'cancelled' o 'suspended' — nada que hacer.
            return null;
        });

        if ($locked === null) {
            return;
        }

        $userId = $locked->creator_user_id;

        try {
            $wallet = $this->walletService->findActiveWallet(
                (string) $userId,
                (string) $locked->currency_id,
            );

            if (!$wallet) {
                $this->recordFailedPayment($locked, 'El ganador no tiene billetera activa en la moneda del premio.');
                return;
            }

            $amount = (float) $locked->local_amount;

            $movement = $this->walletService->recordPrize(
                wallet:      $wallet,
                amount:      $amount,
                gameId:      (string) $locked->room_id,
                description: "Premio Bingo — Sala #{$locked->room_id}",
            );

            DB::transaction(function () use ($locked, $userId, $amount, $movement) {
                BingoCardPrizePayment::create([
                    'card_prize_id'          => $locked->id,
                    'payment_method'         => 'system_payment',
                    'room_id'                => $locked->room_id,
                    'creator_user_id'        => $locked->creator_user_id,
                    'seller_user_id'         => $locked->seller_user_id,
                    'seller_type'            => $locked->seller_type,
                    'user_id'                => $userId,
                    'currency_id'            => $locked->currency_id,
                    'payment_amount'         => $amount,
                    'payment_proof'          => 'system_auto',
                    'payment_status'         => 'completed',
                    'processed_at'           => now(),
                    'transaction_reference'  => (string) $movement->id,
                ]);

                $locked->markAsClaimed();
            });

            Log::info('[BingoPrizePayoutService] Premio acreditado a la wallet del ganador', [
                'card_prize_id' => $locked->id,
                'user_id'       => $userId,
                'wallet_id'     => $wallet->id,
                'amount'        => $amount,
            ]);
        } catch (\Throwable $e) {
            Log::error('[BingoPrizePayoutService] Falló el registro del pago tras acreditar la wallet — prize_status queda en processing; el siguiente reintento detectará el pago completado y solo reparará el estado', [
                'card_prize_id' => $locked->id,
                'user_id'       => $userId,
                'error'         => $e->getMessage(),
            ]);

            $this->recordFailedPayment($locked, $e->getMessage());
        }
    }

    /**
     * Deja constancia del intento fallido sin tocar prize_status — el premio
     * sigue en 'waiting' y se reintenta vía bingo:retry-failed-prize-payouts
     * (programado cada 5 minutos en routes/console.php).
     */
    private function recordFailedPayment(BingoCardPrize $cardPrize, string $reason): void
    {
        BingoCardPrizePayment::create([
            'card_prize_id'   => $cardPrize->id,
            'payment_method'  => 'system_payment',
            'room_id'         => $cardPrize->room_id,
            'creator_user_id' => $cardPrize->creator_user_id,
            'seller_user_id'  => $cardPrize->seller_user_id,
            'seller_type'     => $cardPrize->seller_type,
            'user_id'         => $cardPrize->creator_user_id,
            'currency_id'     => $cardPrize->currency_id,
            'payment_amount'  => $cardPrize->local_amount,
            'payment_proof'   => null,
            'payment_status'  => 'failed',
            'payment_notes'   => $reason,
        ]);
    }
}
