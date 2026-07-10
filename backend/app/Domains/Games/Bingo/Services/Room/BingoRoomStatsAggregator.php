<?php
declare(strict_types=1);

namespace App\Domains\Games\Bingo\Services\Room;

use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Draw\BingoDraw;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomStat;
use App\Domains\Games\Bingo\Models\Round\BingoRound;
use Illuminate\Support\Facades\DB;

/**
 * Calcula y persiste las estadísticas finales de una sala de bingo.
 *
 * Se invoca al cerrar la sala (FINISHED/CANCELLED) para generar un snapshot
 * inmutable de los KPIs de la partida, útil para reportes y auditoría.
 */
final class BingoRoomStatsAggregator
{
    /**
     * Calcula y guarda (upsert) las estadísticas de una sala.
     */
    public function aggregate(BingoRoom $room): BingoRoomStat
    {
        $roomId = (string) $room->id;

        $lotStats = BingoCardLot::where('room_id', $roomId)
            ->selectRaw('
                COUNT(*) as total_lots,
                COALESCE(SUM(card_count), 0) as total_cards,
                COALESCE(SUM(sale_amount), 0) as total_revenue
            ')
            ->first();

        $totalPlayers = (int) ($room->total_players ?? 0);

        // Número de fichas cantadas en todo el sorteo (todas las rondas)
        $totalChipsDrawn = BingoDraw::where('room_id', $roomId)->count();

        $rounds = BingoRound::where('room_id', $roomId)->get(['id', 'status', 'start_time']);
        $totalRounds   = $rounds->count();
        $winningRounds = BingoRound::where('room_id', $roomId)
            ->whereHas('winners')
            ->count();

        // Duración estimada del sorteo: desde la primera ficha hasta la última
        $firstDraw = BingoDraw::where('room_id', $roomId)->orderBy('created_at')->first(['created_at']);
        $lastDraw  = BingoDraw::where('room_id', $roomId)->orderByDesc('created_at')->first(['created_at']);
        $drawDuration = ($firstDraw && $lastDraw)
            ? (int) $firstDraw->created_at->diffInSeconds($lastDraw->created_at)
            : null;

        $stat = BingoRoomStat::updateOrCreate(
            ['room_id' => $roomId],
            [
                'total_lots'            => (int) ($lotStats?->total_lots ?? 0),
                'total_cards'           => (int) ($lotStats?->total_cards ?? 0),
                'total_players'         => $totalPlayers,
                'total_revenue'         => (float) ($lotStats?->total_revenue ?? 0),
                'total_revenue_usd'     => (float) ($room->metrics_by_currency
                    ? array_sum(array_column($room->metrics_by_currency, 'revenue_usd'))
                    : 0),
                'prize_pool_final'      => (float) ($room->prize_pool ?? 0),
                'total_chips_drawn'     => $totalChipsDrawn,
                'total_rounds'          => $totalRounds,
                'winning_rounds'        => $winningRounds,
                'draw_duration_seconds' => $drawDuration,
                'calculated_at'         => now(),
            ]
        );

        return $stat;
    }
}
