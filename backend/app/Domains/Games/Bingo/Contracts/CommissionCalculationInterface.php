<?php

namespace App\Domains\Games\Bingo\Contracts;

/**
 * Interfaz para el servicio de cálculo de comisiones
 */
interface CommissionCalculationInterface
{
    /**
     * Calcular la comisión del vendedor
     *
     * @param float $saleAmount Monto de la venta
     * @param string|null $sellerId ID del vendedor
     * @param string|null $roomId ID de la sala
     * @return float Comisión calculada
     */
    public function calculateSellerCommission(float $saleAmount, ?string $sellerId = null, ?string $roomId = null): float;

    /**
     * Calcular la comisión de la sala
     *
     * @param float $saleAmount Monto de la venta
     * @return float Comisión de la sala
     */
    public function calculateRoomCommission(float $saleAmount): float;

    /**
     * Calcular la comisión RKE
     *
     * @param float $saleAmount Monto de la venta
     * @return float Comisión RKE
     */
    public function calculateRkeCommission(float $saleAmount): float;

    /**
     * Calcular todas las comisiones para una venta
     *
     * @param float $saleAmount Monto total de la venta
     * @param string|null $sellerId ID del vendedor
     * @param string|null $roomId ID de la sala
     * @return array Array asociativo con todas las comisiones calculadas
     */
    public function calculateAllCommissions(float $saleAmount, ?string $sellerId = null, ?string $roomId = null): array;
}
