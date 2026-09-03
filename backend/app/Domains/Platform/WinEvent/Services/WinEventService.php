<?php

namespace App\Domains\Platform\WinEvent\Services;

use App\Domains\Platform\WinEvent\Models\WinEvent;
use App\Events\Platform\WinEventBroadcasted;
use App\Models\Currency;
use Illuminate\Support\Facades\Log;

/**
 * Único punto de entrada para reportar una ganancia al feed en vivo de la
 * plataforma. Cualquier juego, panel admin, o integración externa debe
 * pasar por aquí — nunca crear WinEvent directamente ni emitir el broadcast
 * a mano, para que el shape del evento sea siempre consistente.
 */
final class WinEventService
{
    /**
     * Registra una ganancia monetaria (dinero real acreditado a una wallet,
     * o el equivalente estimado de un premio físico).
     *
     * @param string|null $userId      Ganador con cuenta en la plataforma (null si no aplica)
     * @param string|null $winnerNameOverride Nombre a mostrar si no hay $userId (premios externos)
     * @param string      $gameSlug    Identificador corto del origen (bingo, trivia, …)
     * @param string      $gameName    Nombre legible del origen
     * @param float       $amountUsd   Monto en USD
     * @param float       $amountLocal Monto en moneda local
     * @param string|null $currencyId  Moneda del monto local
     * @param string      $sourceType  WinEvent::SOURCE_*
     * @param string|null $sourceReference ID del registro de origen (auditoría)
     * @param array|null  $metadata
     */
    public function reportMoney(
        ?string $userId,
        ?string $winnerNameOverride,
        string $gameSlug,
        string $gameName,
        float $amountUsd,
        float $amountLocal,
        ?string $currencyId,
        string $sourceType = WinEvent::SOURCE_GAME_ENGINE,
        ?string $reportedByUserId = null,
        ?string $sourceReference = null,
        ?array $metadata = null,
    ): WinEvent {
        $currency = $currencyId ? Currency::find($currencyId) : null;
        $prizeLabel = $currency
            ? "{$currency->symbol}" . number_format($amountLocal, $currency->decimal_places ?? 2)
            : '$' . number_format($amountUsd, 2);

        return $this->report(
            userId: $userId,
            winnerNameOverride: $winnerNameOverride,
            gameSlug: $gameSlug,
            gameName: $gameName,
            prizeKind: WinEvent::PRIZE_MONEY,
            prizeLabel: $prizeLabel,
            currencyId: $currencyId,
            amountUsd: $amountUsd,
            amountLocal: $amountLocal,
            sourceType: $sourceType,
            reportedByUserId: $reportedByUserId,
            sourceReference: $sourceReference,
            metadata: $metadata,
        );
    }

    /**
     * Registra una ganancia no monetaria (objeto físico o servicio).
     */
    public function reportNonMoney(
        ?string $userId,
        ?string $winnerNameOverride,
        string $gameSlug,
        string $gameName,
        string $prizeKind,
        string $prizeLabel,
        string $sourceType = WinEvent::SOURCE_ADMIN_MANUAL,
        ?string $reportedByUserId = null,
        ?string $sourceReference = null,
        ?array $metadata = null,
    ): WinEvent {
        return $this->report(
            userId: $userId,
            winnerNameOverride: $winnerNameOverride,
            gameSlug: $gameSlug,
            gameName: $gameName,
            prizeKind: $prizeKind,
            prizeLabel: $prizeLabel,
            currencyId: null,
            amountUsd: null,
            amountLocal: null,
            sourceType: $sourceType,
            reportedByUserId: $reportedByUserId,
            sourceReference: $sourceReference,
            metadata: $metadata,
        );
    }

    private function report(
        ?string $userId,
        ?string $winnerNameOverride,
        string $gameSlug,
        string $gameName,
        string $prizeKind,
        string $prizeLabel,
        ?string $currencyId,
        ?float $amountUsd,
        ?float $amountLocal,
        string $sourceType,
        ?string $reportedByUserId,
        ?string $sourceReference,
        ?array $metadata,
    ): WinEvent {
        $winEvent = WinEvent::create([
            'user_id'              => $userId,
            'winner_name_override' => $winnerNameOverride,
            'game_slug'            => $gameSlug,
            'game_name'            => $gameName,
            'source_type'          => $sourceType,
            'reported_by_user_id'  => $reportedByUserId,
            'prize_kind'           => $prizeKind,
            'prize_label'          => $prizeLabel,
            'currency_id'          => $currencyId,
            'amount_usd'           => $amountUsd,
            'amount_local'         => $amountLocal,
            'source_reference'     => $sourceReference,
            'metadata'             => $metadata,
        ]);

        $this->broadcast($winEvent);

        return $winEvent;
    }

    private function broadcast(WinEvent $winEvent): void
    {
        try {
            $winEvent->loadMissing(['user', 'currency']);
            $currency = $winEvent->currency;

            broadcast(new WinEventBroadcasted(
                winEvent:       $winEvent,
                winnerName:     $winEvent->maskedWinnerName(),
                currencySymbol: $currency?->symbol ?? '$',
                decimalPlaces:  $currency?->decimal_places ?? 2,
            ));
        } catch (\Throwable $e) {
            Log::warning('[WinEventService] No se pudo emitir el broadcast: ' . $e->getMessage(), [
                'win_event_id' => $winEvent->id,
            ]);
        }
    }
}
