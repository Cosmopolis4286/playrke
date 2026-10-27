<?php

namespace App\Domains\Games\Bingo\Contracts;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;

/**
 * Interfaz para el servicio de métricas de salas de bingo
 */
interface RoomMetricsInterface
{
    /**
     * Obtener métricas actuales de la sala
     *
     * @param BingoRoom $room Sala de la cual obtener métricas
     *
     * @return array Array asociativo con todas las métricas
     */
    public function getRoomMetrics(BingoRoom $room) : array;

    /**
     * Actualizar todas las métricas de la sala después de una venta
     *
     * @param BingoRoom $room     Sala a actualizar
     * @param array     $saleData Datos de la venta (amount, cards, etc.)
     *
     * @return bool True si se actualizaron todas las métricas correctamente
     */
    public function updateAllMetrics(BingoRoom $room, array $saleData) : bool;

    /**
     * Actualizar el contador de jugadores en la sala
     *
     * @param BingoRoom $room Sala a actualizar
     *
     * @return bool True si se actualizó correctamente
     */
    public function updatePlayerCount(BingoRoom $room) : bool;

    /**
     * Actualizar el pozo de premios de la sala
     *
     * @param BingoRoom $room        Sala a actualizar
     * @param float     $amountToAdd Monto a agregar al pozo
     *
     * @return bool True si se actualizó correctamente
     */
    public function updatePrizePool(BingoRoom $room, float $amountToAdd) : bool;

    /**
     * Actualizar el contador total de cartones en la sala
     *
     * @param BingoRoom $room            Sala a actualizar
     * @param int       $additionalCards Cantidad de cartones adicionales
     *
     * @return bool True si se actualizó correctamente
     */
    public function updateTotalCards(BingoRoom $room, int $additionalCards) : bool;

    /**
     * Actualizar el monto total de ventas de la sala
     *
     * @param BingoRoom $room       Sala a actualizar
     * @param float     $saleAmount Monto de la venta a agregar
     *
     * @return bool True si se actualizó correctamente
     */
    public function updateTotalSales(BingoRoom $room, float $saleAmount) : bool;
}
