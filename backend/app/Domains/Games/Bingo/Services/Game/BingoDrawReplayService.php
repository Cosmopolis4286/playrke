<?php
declare(strict_types=1);

namespace App\Domains\Games\Bingo\Services\Game;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Round\BingoRound;

/**
 * Reconstruye el historial completo de fichas cantadas en un sorteo de bingo.
 *
 * Permite a jugadores y soporte auditar el sorteo tras su finalización:
 * qué ficha se cantó en qué orden, en qué ronda, y cuándo (timestamp).
 */
final class BingoDrawReplayService
{
    /**
     * @param BingoRoom $room
     * @return array{
     *   room_id: string,
     *   status:  string,
     *   rounds:  list<array{
     *     round:    int,
     *     round_id: string,
     *     pattern:  string|null,
     *     chips:    list<array{order: int, chip: int, code: string, drawn_at: string|null}>
     *   }>
     * }
     */
    public function replay(BingoRoom $room): array
    {
        $roomId = (string) $room->id;

        // Eager-load draws para evitar N+1: una query por rounds + una por draws
        $rounds = BingoRound::where('room_id', $roomId)
            ->with([
                'bingoPattern:id,name',
                'draws' => fn ($q) => $q
                    ->orderBy('time')
                    ->orderBy('created_at')
                    ->select(['id', 'round_id', 'chip', 'code', 'time', 'created_at']),
            ])
            ->orderBy('round')
            ->get();

        $result = [];

        foreach ($rounds as $round) {
            $chips = $round->draws->values()->map(fn ($d, int $idx) => [
                'order'    => $idx + 1,
                'chip'     => (int) $d->chip,
                'code'     => (string) $d->code,
                'drawn_at' => $d->created_at?->toISOString(),
            ])->all();

            $result[] = [
                'round'    => (int) $round->round,
                'round_id' => (string) $round->id,
                'status'   => $round->status,
                'pattern'  => $round->bingoPattern?->name,
                'chips'    => $chips,
            ];
        }

        return [
            'room_id' => $roomId,
            'status'  => $room->status?->code ?? 'UNKNOWN',
            'rounds'  => $result,
        ];
    }
}
