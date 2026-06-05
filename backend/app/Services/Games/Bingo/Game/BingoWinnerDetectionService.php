<?php

namespace App\Services\Games\Bingo\Game;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Draw\BingoDraw;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Events\Games\Bingo\BingoWinnerDeclared;
use App\Models\User;
use Illuminate\Support\Facades\Log;

final class BingoWinnerDetectionService
{
    /**
     * Verifica si un cartón tiene bingo (todos los números sorteados).
     *
     * @param BingoCard $card
     * @param array $drawnNumbers Números que han sido sorteados
     * @return bool
     */
    public function checkBingo(BingoCard $card, array $drawnNumbers): bool
    {
        $drawnSet = array_flip($drawnNumbers);
        $allPositions = BingoCard::ALL_POSITIONS;

        foreach ($allPositions as $position) {
            // El centro (N3) es gratis, no necesita estar sorteado
            if ($position === 'n3') {
                continue;
            }

            $number = $card->$position;

            // Si el número no ha sido sorteado, no hay bingo
            if (!isset($drawnSet[$number])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verifica bingo para un usuario específico en una sala.
     *
     * @param BingoRoom $room
     * @param int $userId
     * @return array|null Datos del ganador o null si no hay bingo
     */
    public function checkWinnerForUser(BingoRoom $room, int $userId): ?array
    {
        // Obtener números sorteados de la sala
        $drawnNumbers = BingoDraw::where('room_id', $room->id)
            ->pluck('chip')
            ->map(fn($chip) => (int) $chip)
            ->toArray();

        if (empty($drawnNumbers)) {
            return null;
        }

        // Obtener cartones activos del usuario en esta sala
        $cards = BingoCard::where('room_id', (string)$room->id)
            ->whereHas('roomUser', fn($q) => $q->where('user_id', (string)$userId))
            ->where('status', BingoCard::STATUS_ACTIVE)
            ->get();

        if ($cards->isEmpty()) {
            // Intentar por creator_user_id como fallback
            $cards = BingoCard::where('room_id', (string)$room->id)
                ->where('creator_user_id', (string)$userId)
                ->where('status', BingoCard::STATUS_ACTIVE)
                ->get();
        }

        $user = User::find($userId);

        foreach ($cards as $card) {
            if ($this->checkBingo($card, $drawnNumbers)) {
                // Marcar cartón como ganador
                $card->update(['status' => BingoCard::STATUS_WINNER]);

                $winnerData = [
                    'user_id' => $userId,
                    'user_name' => $user?->name ?? 'Jugador #' . $userId,
                    'card_id' => $card->id,
                    'card_serial' => $card->serial_number,
                    'pattern' => 'Cartón Lleno',
                    'prize' => (float) $room->prize_pool,
                ];

                Log::info('[BingoWinnerDetection] Bingo detectado', [
                    'room_id' => $room->id,
                    'winner' => $winnerData,
                ]);

                return $winnerData;
            }
        }

        return null;
    }

    /**
     * Declara un ganador y dispara evento WebSocket.
     *
     * @param BingoRoom $room
     * @param array $winnerData
     * @return void
     */
    public function declareWinner(BingoRoom $room, array $winnerData): void
    {
        // Obtener el último draw para el draw_id
        $lastDraw = BingoDraw::where('room_id', $room->id)
            ->orderBy('created_at', 'desc')
            ->first();

        broadcast(new BingoWinnerDeclared(
            roomId: (int) $room->id,
            drawId: (int) ($lastDraw?->id ?? 0),
            winnerId: $winnerData['user_id'],
            winnerName: $winnerData['user_name'],
            cardId: (int) $winnerData['card_id'],
            pattern: $winnerData['pattern'],
            prize: $winnerData['prize']
        ));

        Log::info('[BingoWinnerDetection] Evento WinnerDeclared disparado', [
            'room_id' => $room->id,
            'winner' => $winnerData,
        ]);
    }
}
