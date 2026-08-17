<?php

namespace App\Services\Jackpot;

use App\Contracts\JackpotMovementRepositoryInterface;
use App\Contracts\JackpotRepositoryInterface;
use App\Models\JackpotMovement;

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
    ) {}

    public function contribute(
        string $jackpotTypeId,
        float $amount,
        ?string $roomId = null,
        ?string $description = null,
        ?array $metadata = null,
    ): void {
        if ($amount <= 0) {
            return;
        }

        $jackpot = $this->jackpots->firstOrCreateByType($jackpotTypeId);
        $balanceBefore = (float) $jackpot->amount;

        $this->jackpots->incrementAmount((string) $jackpot->id, $amount);
        $jackpot->refresh();

        $this->movements->record([
            'jackpot_id'     => (string) $jackpot->id,
            'room_id'        => $roomId,
            'type'           => JackpotMovement::TYPE_CONTRIBUTION,
            'amount'         => $amount,
            'balance_before' => $balanceBefore,
            'balance_after'  => (float) $jackpot->amount,
            'description'    => $description ?? 'Aporte por venta de cartón',
            'metadata'       => $metadata,
        ]);
    }
}
