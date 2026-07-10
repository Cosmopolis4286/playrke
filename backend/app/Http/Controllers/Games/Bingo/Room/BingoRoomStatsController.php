<?php
declare(strict_types=1);

namespace App\Http\Controllers\Games\Bingo\Room;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomStat;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class BingoRoomStatsController extends Controller
{
    /**
     * GET /bingo-rooms/{room}/stats
     *
     * Devuelve las estadísticas agregadas de la sala (snapshot post-sorteo).
     * Solo disponible una vez que BingoRoomStatsAggregator::aggregate() se haya ejecutado.
     */
    public function __invoke(BingoRoom $room): JsonResponse
    {
        $this->authorize('view', $room);

        $stat = BingoRoomStat::where('room_id', (string) $room->id)->first();

        if (!$stat) {
            return response()->json(['message' => 'Las estadísticas aún no están disponibles para esta sala.'], 404);
        }

        return response()->json($stat);
    }
}
