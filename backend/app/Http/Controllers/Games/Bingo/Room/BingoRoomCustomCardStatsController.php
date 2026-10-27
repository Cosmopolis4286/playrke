<?php

declare(strict_types=1);

namespace App\Http\Controllers\Games\Bingo\Room;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class BingoRoomCustomCardStatsController extends Controller
{
    /**
     * GET /bingo-rooms/{room}/cards/custom-stats
     *
     * Devuelve estadísticas de cartones personalizados para la sala:
     *  - total:          total de cartones activos/históricos en la sala
     *  - custom:         cuántos son is_custom_card = true
     *  - custom_pct:     porcentaje (0–100, 2 decimales)
     *  - custom_winners: cuántos premios fueron ganados con cartones personalizados
     */
    public function __invoke(BingoRoom $room) : JsonResponse
    {
        $this->authorize('view', $room);

        $roomId = (string) $room->id;

        $counts = BingoCard::where('room_id', $roomId)
            ->select([
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN is_custom_card = 1 THEN 1 ELSE 0 END) as custom'),
            ])
            ->first();

        $total = (int) ($counts->total ?? 0);
        $custom = (int) ($counts->custom ?? 0);

        $customWinners = BingoCardPrize::where('room_id', $roomId)
            ->whereHas('winningCard', fn ($q) => $q->where('is_custom_card', true))
            ->count();

        return response()->json([
            'total' => $total,
            'custom' => $custom,
            'custom_pct' => $total > 0 ? round($custom / $total * 100, 2) : 0.0,
            'custom_winners' => $customWinners,
        ]);
    }
}
