<?php

namespace App\Services\Jackpot;

use App\Contracts\JackpotMovementRepositoryInterface;
use App\Contracts\JackpotRepositoryInterface;
use App\Models\Jackpot;
use App\Models\JackpotMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Acumulación del jackpot — SOLO aporta al pozo, no paga ni entrega premios
 * (fuera de alcance de esta iteración). Mismo patrón que
 * PlatformWalletService::creditRke(): UPDATE atómico a nivel SQL vía
 * JackpotRepository::incrementAmount(), sin DB::transaction()/lockForUpdate()
 * explícito.
 */
final class JackpotLedgerService
{
    public function __construct(
        private readonly JackpotRepositoryInterface $jackpots,
        private readonly JackpotMovementRepositoryInterface $movements,
    ) {
    }

    public function contribute(
        string $jackpotTypeId,
        float $amount,
        ?string $roomId = null,
        ?string $description = null,
        ?array $metadata = null,
    ) : void {
        if ($amount <= 0) {
            return;
        }

        $jackpot = $this->jackpots->firstOrCreateByType($jackpotTypeId);
        $balanceBefore = (float) $jackpot->amount;

        $this->jackpots->incrementAmount((string) $jackpot->id, $amount);
        $jackpot->refresh();

        $this->movements->record([
            'jackpot_id' => (string) $jackpot->id,
            'room_id' => $roomId,
            'type' => JackpotMovement::TYPE_CONTRIBUTION,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => (float) $jackpot->amount,
            'description' => $description ?? 'Aporte por venta de cartón',
            'metadata' => $metadata,
        ]);
    }

    /**
     * Revierte el aporte de una venta que luego se reembolsó (sala cancelada o
     * pago rechazado — ver BingoLotSaleReversalService). Sin esto el pozo se
     * quedaba con un aporte de dinero que el jugador recuperó al 100%.
     *
     * Idempotente por $idempotencyKey (el job de reembolso puede reintentarse).
     * Si el pozo ya no alcanza (se pagó), revierte lo que haya y devuelve el
     * faltante para que el caller lo registre en conciliación — nunca deja el
     * pozo en negativo.
     *
     * @return float faltante no revertido (0 si se revirtió completo o ya estaba revertido)
     */
    public function reverseContribution(
        string $jackpotTypeId,
        float $amount,
        string $idempotencyKey,
        ?string $roomId = null,
        ?string $description = null,
    ) : float {
        if ($amount <= 0) {
            return 0.0;
        }
        if (JackpotMovement::where('idempotency_key', $idempotencyKey)->exists()) {
            return 0.0;
        }

        $jackpot = $this->jackpots->firstOrCreateByType($jackpotTypeId);

        return DB::transaction(function() use ($jackpot, $amount, $idempotencyKey, $roomId, $description) {
            $locked = Jackpot::where('id', $jackpot->id)->lockForUpdate()->firstOrFail();
            $balanceBefore = (float) $locked->amount;
            $reversible = min($amount, $balanceBefore);

            if ($reversible < $amount) {
                Log::error('[JackpotLedgerService] reverseContribution(): el pozo no alcanza para revertir el aporte completo — conciliar manualmente', [
                    'jackpot_id' => (string) $locked->id, 'amount' => $amount, 'balance' => $balanceBefore, 'idempotency_key' => $idempotencyKey,
                ]);
            }

            $locked->decrement('amount', $reversible);

            $this->movements->record([
                'jackpot_id' => (string) $locked->id,
                'room_id' => $roomId,
                'type' => JackpotMovement::TYPE_REVERSAL,
                'amount' => $reversible,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore - $reversible,
                'description' => $description ?? 'Reversión de aporte por reembolso',
                'idempotency_key' => $idempotencyKey,
                'metadata' => $reversible < $amount ? ['shortfall' => round($amount - $reversible, 4)] : null,
            ]);

            return round($amount - $reversible, 4);
        });
    }
}
