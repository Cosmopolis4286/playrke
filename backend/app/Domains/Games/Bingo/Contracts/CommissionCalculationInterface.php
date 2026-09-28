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

    /**
     * Reparto en cascada de la comisión de la red comercial para una venta
     * hecha por un vendedor: vendedor (X), agencia (Y − X) y comercializadora
     * (Z − max(X, Y)). Nunca negativo.
     *
     * @return array{
     *     seller_rate: float, subsidiary_rate: float, commerce_rate: float,
     *     seller_amount: float, subsidiary_amount: float, commerce_amount: float,
     *     subsidiary_id: ?string, commerce_id: ?string
     * }
     */
    public function networkSplit(float $saleAmount, ?string $sellerId, ?string $roomId = null): array;
}
