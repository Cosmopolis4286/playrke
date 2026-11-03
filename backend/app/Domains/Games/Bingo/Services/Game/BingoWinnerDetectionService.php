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
        private readonly BingoPatternPositionResolver $patternResolver = new BingoPatternPositionResolver,
    ) {
    }

    /**
     * Verifica si un cartón cumple el patrón activo de la ronda.
     *
     * card_numbers está almacenado como { "B": [n, n, null, n, n], … } donde
     * el índice del array corresponde a la fila (base-0) y null = casilla libre.
     *
     * $cell['free'] es la posición ESTÁTICA de la libre según la definición de
     * variante (BingoPatternPositionResolver), compartida por todos los cartones
     * de la sala. Cuando la sala permite mover la libre (allow_move_free_space),
     * cada cartón puede tener su único null en una celda distinta — por eso,
     * además de $cell['free'], se comprueba si la celda real del cartón es null
     * (solo relevante para variantes con casilla libre; en BINGO_90 null significa
     * "celda vacía estructural", no libre, así que $hasFreeCell lo excluye).
     */
    public function checkBingo(BingoCard $card, array $drawnNumbers, array $patternPositions, string $variant = BingoVariant::BINGO_75) : bool
    {
        $drawnSet = array_flip($drawnNumbers);
        $matrix = $card->card_numbers ?? [];
        $hasFreeCell = BingoVariant::hasFreeCell($variant);

        foreach ($patternPositions as $cell) {
            $num = $matrix[$cell['col']][$cell['row']] ?? null;

            if ($cell['free'] || ($hasFreeCell && $num === null)) {
                continue;
            }

            if ($num === null || ! isset($drawnSet[$num])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verifica bingo para un ExternalPlayer (comprador sin cuenta) en una
     * sala — mismos cartones que ve getUserCartonsForExternalPlayer()
     * (BingoGameService), resueltos vía ExternalPlayerParticipation en vez
     * de user_id, ya que un ExternalPlayer no tiene fila en `users`.
     *
     * El gate anti-fraude de timing se aplica igual (protege el juego
     * completo, no solo cuentas registradas), pero SIN registrar un
     * AntiCheatFlag — esa tabla tiene FK real a `users.id`, que un
     * ExternalPlayer no tiene.
     */
    public function checkWinnerForExternalPlayer(BingoRoom $room, int|string $externalPlayerId) : ?array
    {
        $cardIds = \App\Models\Roles\Player\External\ExternalPlayerParticipation::participableIdsFor(
            $externalPlayerId,
            BingoCard::class,
        );

        if (empty($cardIds)) {
            return null;
        }

        // El mismo scope que utiliza el sorteo automático: lote ACTIVE y
        // todos los pagos asociados aprobados (o ningún pago pendiente de revisión).
        $cards = BingoCard::whereIn('id', $cardIds)
            ->where('status', BingoCard::STATUS_ACTIVE)
            ->eligibleForDraw()
            ->get();

        return $this->checkWinnerForCards($room, $cards, antiCheatUserId: null);
    }

    /**
     * Verifica bingo para un usuario específico en una sala.
     * Usa el patrón de la ronda activa y la variante de la sala.
     */
    public function checkWinnerForUser(BingoRoom $room, int $userId) : ?array
    {
        $cards = BingoCard::where('room_id', (string) $room->id)
            ->where('status', BingoCard::STATUS_ACTIVE)
            ->eligibleForDraw()
            ->where(
                fn ($q) => $q
                    ->whereHas('roomUser', fn ($r) => $r->where('user_id', (string) $userId))
                    ->orWhere('creator_user_id', (string) $userId),
            )
            ->get();

        return $this->checkWinnerForCards($room, $cards, antiCheatUserId: (string) $userId);
    }

    /**
     * Lógica compartida de detección de bingo: patrón activo, gate
     * anti-fraude de timing, y reclamo del premio — independiente de cómo
     * se resolvieron los $cards del jugador (por user_id o por
     * ExternalPlayerParticipation).
     *
     * @param \Illuminate\Support\Collection<int, BingoCard> $cards
     */
    private function checkWinnerForCards(BingoRoom $room, \Illuminate\Support\Collection $cards, ?string $antiCheatUserId) : ?array
    {
        if ($cards->isEmpty()) {
            return null;
        }

        if (! $room->relationLoaded('config')) {
            $room->load('config');
        }

        $variant = $room->config?->game_variant ?? BingoVariant::BINGO_75;

        // Resolver primero la ronda activa. Los números cantados y el último
        // draw deben pertenecer a ESA ronda, nunca a toda la sala.
        $round = BingoRound::where('room_id', (string) $room->id)
            ->whereIn('status', ['ACTIVE', 'IN_PROGRESS'])
            ->orderBy('round')
            ->first();

        if (! $round) {
            return null;
        }

        $drawnNumbers = BingoDraw::where('room_id', $room->id)
            ->where('round_id', (string) $round->id)
            ->pluck('chip')
            ->map(fn ($chip) => (int) $chip)
            ->toArray();

        if (empty($drawnNumbers)) {
            return null;
        }

        $pattern = $round->bingo_pattern_id
            ? BingoPattern::find($round->bingo_pattern_id)
            : null;

        if (! $pattern) {
            return null;
        }

        $patternPositions = $this->patternResolver->resolve($pattern, $variant);

        $lastDraw = BingoDraw::where('room_id', $room->id)
            ->where('round_id', (string) $round->id)
            ->orderBy('created_at', 'desc')
            ->first();
        if (! $lastDraw) {
            return null;
        }
        $lastDrawId = (int) $lastDraw->id;

        // Gate anti-fraude de timing: nadie puede reclamar bingo antes de que
        // sea humanamente posible ver la ficha cantada y confirmar el patrón.
        $elapsedMs = $lastDraw->created_at->diffInMilliseconds(now());
        $minDelayMs = (int) config('anticheat.bingo.min_claim_delay_ms', 400);

        if ($elapsedMs < $minDelayMs) {
            if ($antiCheatUserId !== null) {
                $this->antiCheat->flag(
                    userId: $antiCheatUserId,
                    game: GameSlug::BINGO,
                    signal: AntiCheatSignalType::IMPOSSIBLE_CLAIM_TIMING,
                    gameId: (string) $room->id,
                    metadata: [
                        'last_draw_id' => $lastDrawId,
                        'last_draw_at' => $lastDraw->created_at->toIso8601String(),
                        'elapsed_ms' => $elapsedMs,
                        'min_required_ms' => $minDelayMs,
                    ],
                );
            }

            Log::warning('[BingoWinnerDetection] Timing de reclamo sospechoso — bloqueado', [
                'room_id' => $room->id,
                'user_id' => $antiCheatUserId,
                'elapsed_ms' => $elapsedMs,
            ]);

            // Mismo resultado que "no tiene bingo aún" — no revela al cliente
            // que fue detectado, evitando un oráculo para calibrar el bot.
            return null;
        }

        $remainingChips = BingoVariant::totalChips($variant) - count($drawnNumbers);
        $isLuckyWinner = $remainingChips <= BingoDrawService::LUCKY_WINNER_REMAINING_THRESHOLD;

        foreach ($cards as $card) {
            if (! $this->checkBingo($card, $drawnNumbers, $patternPositions, $variant)) {
                continue;
            }

            // Persistencia + broadcasts bajo lock: si el sorteo automático ya reclamó
            // este cartón en paralelo, claimCardWin devuelve null y seguimos buscando
            $winnerData = $this->drawService->claimCardWin($room, $round, $pattern, (string) $card->id, $lastDrawId, $isLuckyWinner);

            if ($winnerData === null) {
                continue;
            }

            Log::info('[BingoWinnerDetection] Bingo detectado (verificación manual)', [
                'room_id' => $room->id,
                'variant' => $variant,
                'pattern' => $pattern->name,
                'winner' => $winnerData,
            ]);

            return $winnerData;
        }

        return null;
    }
}
