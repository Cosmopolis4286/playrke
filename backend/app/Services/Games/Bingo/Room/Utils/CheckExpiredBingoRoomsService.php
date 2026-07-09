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
        $now = Carbon::now('UTC')->timestamp;

        $statusMap    = GameStatus::codesMap(['INACTIVE', 'IN_PROGRESS', 'ACTIVE']);
        $inactiveId   = $statusMap['INACTIVE']    ?? null;
        $inProgressId = $statusMap['IN_PROGRESS'] ?? null;
        $activeId     = $statusMap['ACTIVE']      ?? null;

        if (!$inactiveId || !$inProgressId || !$activeId) {
            return;
        }

        // Salas ACTIVE cuya fecha+hora ya pasó — dos bulk UPDATE en lugar de N individuales.
        $expiredSubquery = fn($q) => $q->where('unix_date', '<=', $now);

        BingoRoom::where('status_id', $activeId)
            ->whereHas('config', $expiredSubquery)
            ->where('total_players', '>', 0)
            ->update(['status_id' => $inProgressId]);

        BingoRoom::where('status_id', $activeId)
            ->whereHas('config', $expiredSubquery)
            ->where(fn($q) => $q->where('total_players', 0)->orWhereNull('total_players'))
            ->update(['status_id' => $inactiveId]);
    }
}
