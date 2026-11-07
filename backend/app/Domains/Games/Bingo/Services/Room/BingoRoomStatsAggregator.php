<?php

declare(strict_types=1);

namespace App\Domains\Games\Bingo\Services\Room;

use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Draw\BingoDraw;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomStat;
use App\Domains\Games\Bingo\Models\Round\BingoRound;

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
    public function aggregate(BingoRoom $room) : BingoRoomStat
    {
        $roomId = (string) $room->id;

        $counts = app(\App\Domains\Games\Bingo\Services\Room\Metrics\BingoRoomStateCoordinator::class)
            ->synchronizeCounters($roomId);
        $room->refresh();

        // Lotes + cartones + ingresos — una sola query agregada
        $lotStats = BingoCardLot::where('room_id', $roomId)
            ->selectRaw('
                COUNT(*) as total_lots,
                COALESCE(SUM(card_count), 0) as total_cards,
                COALESCE(SUM(sale_amount), 0) as total_revenue
            ')
            ->first();

        // Fichas cantadas en total
        $totalChipsDrawn = BingoDraw::where('room_id', $roomId)->count();

        // Rondas: una query con COUNT condicional para total y ganadoras
        $roundStats = BingoRound::where('room_id', $roomId)
            ->withCount('winners')
            ->get(['id', 'status']);

        $totalRounds = $roundStats->count();
        $winningRounds = $roundStats->filter(fn ($r) => ($r->winners_count ?? 0) > 0)->count();

        // Duración del sorteo: MIN/MAX atómico — evita race condition entre dos queries separadas
        $drawTimes = BingoDraw::where('room_id', $roomId)
            ->selectRaw('MIN(created_at) as first_draw, MAX(created_at) as last_draw')
            ->first();

        $drawDuration = null;
        if ($drawTimes?->first_draw && $drawTimes->last_draw) {
            $drawDuration = (int) \Carbon\Carbon::parse($drawTimes->first_draw)
                ->diffInSeconds(\Carbon\Carbon::parse($drawTimes->last_draw));
        }

        $stat = BingoRoomStat::updateOrCreate(
            ['room_id' => $roomId],
            [
                'total_lots' => (int) ($lotStats?->total_lots ?? 0),
                'total_cards' => $counts['total_cards'],
                'total_players' => $counts['total_players'],
                'total_revenue' => (float) ($lotStats?->total_revenue ?? 0),
                'total_revenue_usd' => (float) ($room->metrics_by_currency
                    ? array_sum(array_column((array) $room->metrics_by_currency, 'revenue_usd'))
                    : 0),
                'prize_pool_final' => (float) ($room->prize_pool ?? 0),
                'total_chips_drawn' => $totalChipsDrawn,
                'total_rounds' => $totalRounds,
                'winning_rounds' => $winningRounds,
                'draw_duration_seconds' => $drawDuration,
                'calculated_at' => now(),
            ],
        );

        return $stat;
    }
}
