<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Evento público que anuncia un ganador en tiempo real a toda la plataforma.
 * Canal: live-wins (público, sin autenticación).
 */
class LiveWinEvent implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly string $game,
        public readonly string $gameSlug,
        public readonly string $winnerName,
        public readonly float  $amount,
        public readonly string $currency,
        public readonly string $currencySymbol,
        public readonly bool   $isUsd,
        public readonly float  $localAmount,
        public readonly int    $decimalPlaces,
        public readonly string $timestamp,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('live-wins');
    }

    public function broadcastAs(): string
    {
        return 'NewWin';
    }

    public function broadcastWith(): array
    {
        return [
            'game'           => $this->game,
            'game_slug'      => $this->gameSlug,
            'winner_name'    => $this->winnerName,
            'amount'         => $this->amount,
            'currency'       => $this->currency,
            'currency_symbol'=> $this->currencySymbol,
            'is_usd'         => $this->isUsd,
            'local_amount'   => $this->localAmount,
            'decimal_places' => $this->decimalPlaces,
            'timestamp'      => $this->timestamp,
        ];
    }
}
