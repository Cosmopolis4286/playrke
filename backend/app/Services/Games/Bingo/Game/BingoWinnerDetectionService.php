<?php

namespace App\Services\Games\Bingo\Game;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Draw\BingoDraw;
use App\Domains\Games\Bingo\Models\Pattern\BingoPattern;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Round\BingoRound;
use App\Domains\Games\Bingo\ValueObjects\BingoVariant;
use Illuminate\Support\Facades\Log;

/**
 * Verifica si un jugador completó su cartón cuando lo solicita explícitamente
 * (botón "Bingo" en el frontend). La persistencia del premio y los broadcasts
 * se delegan siempre a BingoDrawService::claimCardWin() — el mismo punto único
 * que usa el sorteo automático — para que ambos caminos no puedan marcar el
 * mismo cartón como ganador dos veces ni dejar premios sin registrar.
 */
final class BingoWinnerDetectionService
{
    public function __construct(
        private readonly BingoDrawService $drawService,
        private readonly BingoPatternPositionResolver $patternResolver = new BingoPatternPositionResolver()
    ) {}

    /**
     * Verifica si un cartón cumple el patrón activo de la ronda.
     *
     * card_numbers está almacenado como { "B": [n, n, null, n, n], … } donde
     * el índice del array corresponde a la fila (base-0) y null = casilla libre.
     */
    public function checkBingo(BingoCard $card, array $drawnNumbers, array $patternPositions): bool
    {
        $drawnSet = array_flip($drawnNumbers);
        $matrix   = $card->card_numbers ?? [];

        foreach ($patternPositions as $cell) {
            if ($cell['free']) continue;

            $num = $matrix[$cell['col']][$cell['row']] ?? null;

            if ($num === null || !isset($drawnSet[$num])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verifica bingo para un usuario específico en una sala.
     * Usa el patrón de la ronda activa y la variante de la sala.
     */
    public function checkWinnerForUser(BingoRoom $room, int $userId): ?array
    {
        if (!$room->relationLoaded('config')) {
            $room->load('config');
        }

        $variant = $room->config?->game_variant ?? BingoVariant::BINGO_75;

        $drawnNumbers = BingoDraw::where('room_id', $room->id)
            ->pluck('chip')
            ->map(fn($chip) => (int) $chip)
            ->toArray();

        if (empty($drawnNumbers)) {
            return null;
        }

        // Obtener ronda activa
        $round = BingoRound::where('room_id', (string) $room->id)
            ->whereIn('status', ['ACTIVE', 'IN_PROGRESS'])
            ->orderBy('round')
            ->first();

        $pattern = $round ? BingoPattern::find($round->bingo_pattern_id) : null;

        // Fallback: patrón "Cartón Lleno" (ALL)
        if (!$pattern) {
            $pattern = BingoPattern::where('default_pattern', 'Y')->first()
                ?? BingoPattern::first();
        }

        if (!$pattern) {
            return null;
        }

        $patternPositions = $this->patternResolver->resolve($pattern, $variant);

        // Cartones activos del usuario
        $cards = BingoCard::where('room_id', (string) $room->id)
            ->whereHas('roomUser', fn($q) => $q->where('user_id', (string) $userId))
            ->where('status', BingoCard::STATUS_ACTIVE)
            ->get();

        if ($cards->isEmpty()) {
            $cards = BingoCard::where('room_id', (string) $room->id)
                ->where('creator_user_id', (string) $userId)
                ->where('status', BingoCard::STATUS_ACTIVE)
                ->get();
        }

        $lastDraw   = BingoDraw::where('room_id', $room->id)->orderBy('created_at', 'desc')->first();
        $lastDrawId = (int) ($lastDraw?->id ?? 0);

        foreach ($cards as $card) {
            if (!$this->checkBingo($card, $drawnNumbers, $patternPositions)) {
                continue;
            }

            // Persistencia + broadcasts bajo lock: si el sorteo automático ya reclamó
            // este cartón en paralelo, claimCardWin devuelve null y seguimos buscando
            // (aunque en la práctica solo puede haber un cartón ganador por usuario aquí).
            $winnerData = $this->drawService->claimCardWin($room, $round, $pattern, (string) $card->id, $lastDrawId);

            if ($winnerData === null) {
                continue;
            }

            Log::info('[BingoWinnerDetection] Bingo detectado (verificación manual)', [
                'room_id' => $room->id,
                'variant' => $variant,
                'pattern' => $pattern->name,
                'winner'  => $winnerData,
            ]);

            return $winnerData;
        }

        return null;
    }
}
