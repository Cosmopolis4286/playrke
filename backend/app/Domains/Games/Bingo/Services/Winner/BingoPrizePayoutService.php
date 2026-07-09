<?php

namespace App\Domains\Games\Bingo\Services\Winner;

use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrizePayment;
use App\Services\WalletService;
use Illuminate\Support\Facades\Log;

/**
 * Acredita el monto de un BingoCardPrize a la billetera del ganador.
 *
 * Se invoca siempre DESPUÉS de que BingoDrawService::claimCardWin() confirma
 * el premio (fuera de esa transacción): el registro del premio nunca debe
 * revertirse porque falle el pago — si la wallet no se puede acreditar, el
 * premio queda en prize_status = 'waiting' para reintentar, y se deja un
 * BingoCardPrizePayment con payment_status = 'failed' para trazabilidad.
 */
final class BingoPrizePayoutService
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function payout(BingoCardPrize $cardPrize): void
    {
        // Idempotencia: si ya fue acreditado (p.ej. el comando de reintento o un
        // segundo disparo de claimCardWin lo invoca de nuevo sobre el mismo
        // registro), no volver a incrementar la wallet del ganador.
        if (!$cardPrize->isWaiting()) {
            return;
        }

        $userId = $cardPrize->creator_user_id;

        try {
            $wallet = $this->walletService->findActiveWallet(
                (string) $userId,
                (string) $cardPrize->currency_id,
            );

            if (!$wallet) {
                $this->recordFailedPayment($cardPrize, 'El ganador no tiene billetera activa en la moneda del premio.');
                return;
            }

            $amount = (float) $cardPrize->local_amount;

            $movement = $this->walletService->recordPrize(
                wallet:      $wallet,
                amount:      $amount,
                gameId:      (string) $cardPrize->room_id,
                description: "Premio Bingo — Sala #{$cardPrize->room_id}",
            );

            BingoCardPrizePayment::create([
                'card_prize_id'          => $cardPrize->id,
                'payment_method'         => 'system_payment',
                'room_id'                => $cardPrize->room_id,
                'creator_user_id'        => $cardPrize->creator_user_id,
                'seller_user_id'         => $cardPrize->seller_user_id,
                'seller_type'            => $cardPrize->seller_type,
                'user_id'                => $userId,
                'currency_id'            => $cardPrize->currency_id,
                'payment_amount'         => $amount,
                'payment_proof'          => 'system_auto',
                'payment_status'         => 'completed',
                'processed_at'           => now(),
                'transaction_reference'  => (string) $movement->id,
            ]);

            $cardPrize->markAsClaimed();

            Log::info('[BingoPrizePayoutService] Premio acreditado a la wallet del ganador', [
                'card_prize_id' => $cardPrize->id,
                'user_id'       => $userId,
                'wallet_id'     => $wallet->id,
                'amount'        => $amount,
            ]);
        } catch (\Throwable $e) {
            Log::error('[BingoPrizePayoutService] Falló la acreditación del premio', [
                'card_prize_id' => $cardPrize->id,
                'user_id'       => $userId,
                'error'         => $e->getMessage(),
            ]);

            $this->recordFailedPayment($cardPrize, $e->getMessage());
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
