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
     * Tasa (%) de comisión efectiva del vendedor en una sala (especial de la
     * sala o base, + bono progresivo) — misma que usa calculateSellerCommission().
     */
    public function sellerCommissionRate(?string $sellerId, ?string $roomId = null): float;
}
