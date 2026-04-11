<?php

declare(strict_types=1);

namespace App\Domains\Trivia\Events;

use App\Domains\Trivia\DTOs\LeaderboardDTO;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento de dominio: Puntuación Actualizada.
 * 
 * Se emite cuando el leaderboard cambia para mantener
 * a todos los jugadores sincronizados en tiempo real.
 */
class ScoreUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly LeaderboardDTO $leaderboard,
        public readonly ?string $highlightedPlayerId = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('trivia.game.' . $this->leaderboard->gameId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'trivia.score.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'game_id' => $this->leaderboard->gameId,
            'leaderboard' => $this->leaderboard->toArray(),
            'highlighted_player_id' => $this->highlightedPlayerId,
            'updated_at' => $this->leaderboard->updatedAt,
        ];
    }
}
