<?php

declare(strict_types=1);

namespace App\Domains\Games\Bingo\Services\Room;

use App\Domains\Games\Bingo\Exceptions\Room\BingoRoomException;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use Illuminate\Support\Facades\DB;

/**
 * Verifica el cupo de una sala antes de permitir un nuevo jugador.
 *
 * Usa lockForUpdate() para prevenir race conditions de overbooking
 * cuando dos requests llegan simultáneamente al mismo sorteo.
 *
 * Uso (dentro de una transacción):
 *   DB::transaction(function() use ($room) {
 *       BingoRoomCapacityGuard::assertHasCapacity($room);
 *       // ... registro del jugador ...
 *   });
 */
final class BingoRoomCapacityGuard
{
    /**
     * Verifica que la sala tenga cupo disponible.
     * Debe llamarse DENTRO de DB::transaction() con lockForUpdate() activo.
     *
     * @throws BingoRoomException si la sala está llena o no tiene config de cupo
     */
    public static function assertHasCapacity(BingoRoom $room) : void
    {
        $room->loadMissing('config');
        $maxPlayers = $room->config?->max_players ?? null;

        if ($maxPlayers === null) {
            // Sin límite configurado — permitir entrada.
            return;
        }

        // Re-lee total_players con lock para serializar escrituras concurrentes.
        $current = BingoRoom::where('id', $room->id)
            ->lockForUpdate()
            ->value('total_players') ?? 0;

        if ((int) $current >= (int) $maxPlayers) {
            throw BingoRoomException::roomFull();
        }
    }

    public static function hasCapacity(BingoRoom $room) : bool
    {
        try {
            self::assertHasCapacity($room);

            return true;
        } catch (BingoRoomException) {
            return false;
        }
    }
}
