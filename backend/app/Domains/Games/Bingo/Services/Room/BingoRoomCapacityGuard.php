<?php

declare(strict_types=1);

namespace App\Domains\Games\Bingo\Services\Room;

use App\Domains\Games\Bingo\Exceptions\Room\BingoRoomException;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Room\Metrics\BingoRoomStateCoordinator;

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
    public static function assertHasCapacity(BingoRoom $room, ?string $pendingUserId = null, ?string $pendingExternalPlayerId = null) : void
    {
        $room->loadMissing('config');
        $maxPlayers = $room->config?->max_players ?? null;

        if ($maxPlayers === null) {
            // Sin límite configurado — permitir entrada.
            return;
        }

        // El coordinador cuenta participantes persistidos y agrega al comprador
        // actual sin confundir al vendedor POS con un jugador.
        $current = app(BingoRoomStateCoordinator::class)->countPlayersForCapacity(
            (string) $room->id,
            $pendingUserId,
            $pendingExternalPlayerId,
        );

        if ((int) $current >= (int) $maxPlayers) {
            throw BingoRoomException::roomFull();
        }
    }

    public static function hasCapacity(BingoRoom $room, ?string $pendingUserId = null, ?string $pendingExternalPlayerId = null) : bool
    {
        try {
            self::assertHasCapacity($room, $pendingUserId, $pendingExternalPlayerId);

            return true;
        } catch (BingoRoomException) {
            return false;
        }
    }
}
