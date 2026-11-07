<?php

declare(strict_types=1);

namespace App\Domains\Games\Bingo\Services\Room;

use App\Domains\Games\Bingo\Exceptions\Room\BingoRoomException;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Room\Metrics\BingoRoomStateCoordinator;

/**
 * Verifica el tope de cartones de una sala (max_cards_to_start) antes de
 * generar un nuevo lote.
 *
 * Antes de este guard, max_cards_to_start era puramente informativo (ver
 * comentario histórico en BingoRoomCrudService sobre "no existe guard de
 * capacidad de cartones análogo a BingoRoomCapacityGuard") — una sala podía
 * venderse por encima de su propio tope configurado, y el "Queda un X%" /
 * barra de venta del lobby (ver BingoGameRoomsDTO::cards_sold_pct) mostraba
 * un límite que el backend nunca hacía cumplir.
 *
 * Mismo patrón que BingoRoomCapacityGuard (jugadores): lockForUpdate() sobre
 * bingo_rooms para serializar escrituras concurrentes y evitar overselling
 * cuando dos compras llegan casi simultáneas al mismo tope.
 *
 * Uso (dentro de una transacción, ANTES de crear el lote/cartones):
 *   DB::transaction(function() use ($room, $cardCount) {
 *       BingoRoomCardCapacityGuard::assertHasCapacityFor($room, $cardCount);
 *       // ... createCardLot() + generateAndStoreCards() ...
 *   });
 */
final class BingoRoomCardCapacityGuard
{
    /**
     * Verifica que la sala tenga cupo para $cardCount cartones más.
     * Debe llamarse DENTRO de DB::transaction() con lockForUpdate() activo.
     *
     * @throws BingoRoomException si la compra excedería el tope de la sala
     */
    public static function assertHasCapacityFor(BingoRoom $room, int $cardCount) : void
    {
        $room->loadMissing('config');
        $maxCards = $room->config?->max_cards_to_start ?? null;

        if ($maxCards === null) {
            // Sin tope configurado — permitir la venta.
            return;
        }

        // El coordinador serializa por sala y cuenta solo lotes ACTIVE, igual
        // que la reconciliación de contadores y el proceso de reembolso.
        $current = app(BingoRoomStateCoordinator::class)->countCards((string) $room->id);

        if ((int) $current + $cardCount > (int) $maxCards) {
            $remaining = max(0, (int) $maxCards - (int) $current);

            throw BingoRoomException::cardLimitExceeded($remaining, $cardCount);
        }
    }
}
