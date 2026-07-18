<?php

namespace App\Domains\Games\Bingo\Services\Game;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Draw\BingoDraw;
use App\Domains\Games\Bingo\Models\Pattern\BingoPattern;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Round\BingoRound;
use App\Domains\Games\Bingo\ValueObjects\BingoVariant;
use App\Enums\Games\Common\AntiCheatSignalType;
use App\Enums\Games\Common\GameSlug;
use App\Services\Games\AntiCheatService;
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
        private readonly AntiCheatService $antiCheat,
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

        if (!$round) {
            return null;
        }

        $pattern = $round->bingo_pattern_id
            ? BingoPattern::find($round->bingo_pattern_id)
            : null;

        if (!$pattern) {
            return null;
        }

        $patternPositions = $this->patternResolver->resolve($pattern, $variant);

        // Cartones activos del usuario — primero por room_user, fallback por creator
        $cards = BingoCard::where('room_id', (string) $room->id)
            ->where('status', BingoCard::STATUS_ACTIVE)
            ->where(fn ($q) => $q
                ->whereHas('roomUser', fn ($r) => $r->where('user_id', (string) $userId))
                ->orWhere('creator_user_id', (string) $userId)
            )
            ->get();

        $lastDraw = BingoDraw::where('room_id', $room->id)->orderBy('created_at', 'desc')->first();
        if (!$lastDraw) {
            return null;
        }
        $lastDrawId = (int) $lastDraw->id;

        // Gate anti-fraude de timing: nadie puede reclamar bingo antes de que
        // sea humanamente posible ver la ficha cantada y confirmar el patrón.
        $elapsedMs  = $lastDraw->created_at->diffInMilliseconds(now());
        $minDelayMs = (int) config('anticheat.bingo.min_claim_delay_ms', 400);

        if ($elapsedMs < $minDelayMs) {
            $this->antiCheat->flag(
                userId: (string) $userId,
                game: GameSlug::BINGO,
                signal: AntiCheatSignalType::IMPOSSIBLE_CLAIM_TIMING,
                gameId: (string) $room->id,
                metadata: [
                    'last_draw_id'    => $lastDrawId,
                    'last_draw_at'    => $lastDraw->created_at->toIso8601String(),
                    'elapsed_ms'      => $elapsedMs,
                    'min_required_ms' => $minDelayMs,
                ],
            );

            Log::warning('[BingoWinnerDetection] Timing de reclamo sospechoso — bloqueado', [
                'room_id'    => $room->id,
                'user_id'    => $userId,
                'elapsed_ms' => $elapsedMs,
            ]);

            // Mismo resultado que "no tiene bingo aún" — no revela al cliente
            // que fue detectado, evitando un oráculo para calibrar el bot.
            return null;
        }

        foreach ($cards as $card) {
            if (!$this->checkBingo($card, $drawnNumbers, $patternPositions)) {
                continue;
            }

            // Persistencia + broadcasts bajo lock: si el sorteo automático ya reclamó
            // este cartón en paralelo, claimCardWin devuelve null y seguimos buscando
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
