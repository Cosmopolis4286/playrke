<?php

namespace App\Services\Games\Bingo\Room\Utils;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\Games\Common\Utils\GameStatus;
use Carbon\Carbon;

/**
 * Verifica salas de bingo cuya fecha+hora programada ya expiró (unix_date <= ahora)
 * y actualiza su estado según si tienen jugadores o no:
 *   - total_players == 0  → INACTIVE
 *   - total_players  > 0  → IN_PROGRESS
 */
final class CheckExpiredBingoRoomsService
{
    public function handle(): void
    {
        $now = Carbon::now('America/Caracas')->timestamp;

        $inactiveId    = GameStatus::where('code', 'INACTIVE')->value('id');
        $inProgressId  = GameStatus::where('code', 'IN_PROGRESS')->value('id');
        $activeId      = GameStatus::where('code', 'ACTIVE')->value('id');

        if (!$inactiveId || !$inProgressId || !$activeId) {
            return;
        }

        // Salas ACTIVE cuya fecha+hora ya pasó
        BingoRoom::where('status_id', $activeId)
            ->whereHas('config', fn($q) => $q->where('unix_date', '<=', $now))
            ->get(['id', 'total_players'])
            ->each(function (BingoRoom $room) use ($inactiveId, $inProgressId) {
                $newStatusId = ($room->total_players ?? 0) > 0
                    ? $inProgressId
                    : $inactiveId;

                $room->update(['status_id' => $newStatusId]);
            });
    }
}
