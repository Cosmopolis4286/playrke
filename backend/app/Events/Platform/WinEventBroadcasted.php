<?php

namespace App\Events\Platform;

use App\Domains\Platform\WinEvent\Models\WinEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Evento público que anuncia una ganancia en tiempo real a toda la
 * plataforma — cualquier juego o premio (dinero, objeto o servicio).
 * Canal: platform.win-events (público, sin autenticación).
 */
class WinEventBroadcasted implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly WinEvent $winEvent,
        public readonly string $winnerName,
        public readonly string $currencySymbol,
        public readonly int $decimalPlaces,
    ) {
    }

    public function broadcastAs() : string
    {
        return 'NewWin';
    }

    public function broadcastConnection() : string
    {
        return 'reverb';
    }

    public function broadcastOn() : Channel
    {
        return new Channel('platform.win-events');
    }

    public function broadcastWith() : array
    {
        $e = $this->winEvent;

        return [
            'id' => (string) $e->id,
            'game' => $e->game_name,
            'game_slug' => $e->game_slug,
            'winner_name' => $this->winnerName,
            'prize_kind' => $e->prize_kind,
            'prize_label' => $e->prize_label,
            'amount' => $e->amount_usd !== null ? (float) $e->amount_usd : null,
            'currency' => $e->currency?->iso_code ?? 'USD',
            'currency_symbol' => $this->currencySymbol,
            'is_usd' => ($e->currency?->iso_code ?? 'USD') === 'USD',
            'local_amount' => $e->amount_local !== null ? (float) $e->amount_local : null,
            'decimal_places' => $this->decimalPlaces,
            'timestamp' => $e->created_at?->toISOString() ?? now()->toISOString(),
        ];
    }
}
