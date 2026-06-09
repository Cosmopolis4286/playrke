<?php

namespace App\Services\Games\Bingo\Game;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Draw\BingoDraw;
use App\Domains\Games\Bingo\Models\Pattern\BingoPattern;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Round\BingoRound;
use App\Domains\Games\Bingo\ValueObjects\BingoVariant;
use App\Events\Games\Bingo\BingoWinnerDeclared;
use App\Models\User;
use Illuminate\Support\Facades\Log;

final class BingoWinnerDetectionService
{
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

        $patternPositions = $this->resolvePatternPositions($pattern, $variant);

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

        $user = User::find($userId);

        foreach ($cards as $card) {
            if ($this->checkBingo($card, $drawnNumbers, $patternPositions)) {
                $card->update(['status' => BingoCard::STATUS_WINNER]);

                $winnerData = [
                    'user_id'     => $userId,
                    'user_name'   => $user?->name ?? 'Jugador #' . $userId,
                    'card_id'     => $card->id,
                    'card_serial' => $card->serial_number,
                    'pattern'     => $pattern->name,
                    'prize'       => (float) $room->prize_pool,
                ];

                Log::info('[BingoWinnerDetection] Bingo detectado', [
                    'room_id'  => $room->id,
                    'variant'  => $variant,
                    'pattern'  => $pattern->name,
                    'winner'   => $winnerData,
                ]);

                return $winnerData;
            }
        }

        return null;
    }

    public function declareWinner(BingoRoom $room, array $winnerData): void
    {
        $lastDraw = BingoDraw::where('room_id', $room->id)
            ->orderBy('created_at', 'desc')
            ->first();

        broadcast(new BingoWinnerDeclared(
            roomId:     (int) $room->id,
            drawId:     (int) ($lastDraw?->id ?? 0),
            winnerId:   $winnerData['user_id'],
            winnerName: $winnerData['user_name'],
            cardId:     (int) $winnerData['card_id'],
            pattern:    $winnerData['pattern'],
            prize:      $winnerData['prize']
        ));

        Log::info('[BingoWinnerDetection] Evento WinnerDeclared disparado', [
            'room_id' => $room->id,
            'winner'  => $winnerData,
        ]);
    }

    /**
     * Idéntico al resolvePatternPositions del BingoDrawService pero standalone
     * para que este servicio no dependa de él.
     *
     * @return array<int, array{col: string, row: int, free: bool}>
     */
    private function resolvePatternPositions(BingoPattern $pattern, string $variant): array
    {
        $colDefs  = BingoVariant::columns($variant);
        $numRows  = BingoVariant::rows($variant);
        $labels   = array_column($colDefs, 'label');
        $resolved = [];

        $boxes = $pattern->boxes ?? '';

        if (strtoupper(trim($boxes)) === 'ALL') {
            foreach ($colDefs as $col) {
                for ($row = 0; $row < $numRows; $row++) {
                    $isFree     = ($col['freeRow'] !== null && $row === $col['freeRow']);
                    $resolved[] = ['col' => $col['label'], 'row' => $row, 'free' => $isFree];
                }
            }
            return $resolved;
        }

        if ($pattern->type_pattern === 'MULTIPLE') {
            foreach ($pattern->boxes_array as $numId) {
                $numId = (int) $numId;
                if ($numId < 1) continue;
                $subPattern = BingoPattern::where('num_id', $numId)->whereNull('user_id')->first();
                if ($subPattern) {
                    $resolved = array_merge($resolved, $this->resolvePatternPositions($subPattern, $variant));
                }
            }
            return $resolved;
        }

        foreach ($pattern->boxes_array as $box) {
            $box    = strtoupper(trim((string) $box));
            $col    = $box[0] ?? '';
            $rowNum = (int) substr($box, 1);

            if (!in_array($col, $labels, true) || $rowNum < 1 || $rowNum > $numRows) continue;

            $rowIndex = $rowNum - 1;
            $isFree   = false;

            foreach ($colDefs as $colDef) {
                if ($colDef['label'] === $col && $colDef['freeRow'] !== null && $colDef['freeRow'] === $rowIndex) {
                    $isFree = true;
                    break;
                }
            }

            $resolved[] = ['col' => $col, 'row' => $rowIndex, 'free' => $isFree];
        }

        return $resolved;
    }
}
