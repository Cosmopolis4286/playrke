<?php

namespace App\Domains\Games\Bingo\Services\Payment;

use App\Models\Roles\Seller;
use App\Models\Pivots\Roles\Seller\SellerRoomLimits;
use App\Domains\Games\Bingo\Contracts\CommissionCalculationInterface;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para el cálculo de comisiones en transacciones de bingo
 */
class CommissionCalculationService implements CommissionCalculationInterface
{
    /**
     * Calcular la comisión del vendedor
     *
     * @param float $saleAmount Monto de la venta
     * @param string|null $sellerId ID del vendedor
     * @param string|null $roomId ID de la sala
     * @return float Comisión calculada
     */
    public function calculateSellerCommission(float $saleAmount, ?string $sellerId = null, ?string $roomId = null): float
    {
        try {
            if (!$sellerId) {
                return 0;
            }

            // Obtener comisión específica para la sala o usar comisión general del vendedor
            $commnSeller = SellerRoomLimits::getGameRoomValueBySeller('BINGO', $sellerId, $roomId, 'commission') 
                ?? Seller::getValueById($sellerId, 'commission');

            if (!$commnSeller) {
                return 0;
            }

            return round($saleAmount * ($commnSeller / 100), 2);
        } catch (\Throwable $e) {
            Log::error('Error al calcular comisión del vendedor: ' . $e->getMessage(), [
                'sellerId' => $sellerId,
                'roomId' => $roomId,
                'saleAmount' => $saleAmount
            ]);
            return 0;
        }
    }

    /**
     * Calcular la comisión de la sala
     *
     * @param float $saleAmount Monto de la venta
     * @return float Comisión de la sala
     */
    public function calculateRoomCommission(float $saleAmount): float
    {
        try {
            // Porcentaje de comisión de la sala (configurable)
            $roomCommissionRate = 0.05; // 5% por defecto
            
            return round($saleAmount * $roomCommissionRate, 2);
        } catch (\Throwable $e) {
            Log::error('Error al calcular comisión de la sala: ' . $e->getMessage(), [
                'saleAmount' => $saleAmount
            ]);
            return 0;
        }
    }

    /**
     * Calcular la comisión RKE
     *
     * @param float $saleAmount Monto de la venta
     * @return float Comisión RKE
     */
    public function calculateRkeCommission(float $saleAmount): float
    {
        try {
            // Porcentaje de comisión RKE (configurable)
            $rkeCommissionRate = 0.10; // 10% por defecto
            
            return round($saleAmount * $rkeCommissionRate, 2);
        } catch (\Throwable $e) {
            Log::error('Error al calcular comisión RKE: ' . $e->getMessage(), [
                'saleAmount' => $saleAmount
            ]);
            return 0;
        }
    }

    /**
     * Calcular todas las comisiones para una venta
     *
     * @param float $saleAmount Monto total de la venta
     * @param string|null $sellerId ID del vendedor
     * @param string|null $roomId ID de la sala
     * @return array Array asociativo con todas las comisiones calculadas
     */
    public function calculateAllCommissions(float $saleAmount, ?string $sellerId = null, ?string $roomId = null): array
    {
        try {
            return [
                'seller_commission' => $this->calculateSellerCommission($saleAmount, $sellerId, $roomId),
                'room_commission' => $this->calculateRoomCommission($saleAmount),
                'rke_commission' => $this->calculateRkeCommission($saleAmount),
                'total_commissions' => 0, // Se calculará después
            ];
        } catch (\Throwable $e) {
            Log::error('Error al calcular todas las comisiones: ' . $e->getMessage(), [
                'saleAmount' => $saleAmount,
                'sellerId' => $sellerId,
                'roomId' => $roomId
            ]);
            
            return [
                'seller_commission' => 0,
                'room_commission' => 0,
                'rke_commission' => 0,
                'total_commissions' => 0,
            ];
        }
    }

    /**
     * Obtener las tasas de comisión configuradas
     *
     * @return array Array con las tasas actuales
     */
    public function getCommissionRates(): array
    {
        return [
            'room_commission_rate' => 0.05,  // 5%
            'rke_commission_rate' => 0.10,   // 10%
        ];
    }

    /**
     * Validar que el monto de venta sea válido para cálculo de comisiones
     *
     * @param float $saleAmount Monto a validar
     * @return bool True si es válido
     */
    public function validateSaleAmount(float $saleAmount): bool
    {
        return $saleAmount > 0 && $saleAmount <= 999999.99;
    }
}
