<?php

declare(strict_types=1);

namespace App\Domains\Games\Bingo\Services\Room;

use App\Domains\Games\Bingo\Exceptions\Room\BingoRoomException;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Room\Metrics\BingoRoomMetricsSynchronizer;
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
    public static function assertHasCapacity(BingoRoom $room, ?string $pendingExternalPlayerId = null, ?string $pendingUserId = null) : void
    {
        $room->loadMissing('config');
        $maxPlayers = $room->config?->max_players ?? null;

        if ($maxPlayers === null) {
            // Sin límite configurado — permitir entrada.
            return;
        }

        // El caller debe tener bloqueada la fila de la sala. El agregado
        // total_players puede estar desfasado; el cupo se decide desde registros.
        $current = app(BingoRoomMetricsSynchronizer::class)->countPlayersForCapacity((string) $room->id, $pendingExternalPlayerId, $pendingUserId);

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
