<?php

namespace App\Domains\Games\Bingo\Services\Payment;

use App\Models\Roles\Seller;
use App\Models\Pivots\Roles\Seller\SellerRoomLimits;
use App\Domains\Games\Bingo\Contracts\CommissionCalculationInterface;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para el cálculo de comisiones en transacciones de bingo.
 *
 * Solo cubre la comisión del vendedor. La comisión de sala/RKE se calcula en
 * RoomMetricsService::updateRoomMetricsWithCommissions() con tasas configurables
 * por sala (bingo_rooms_config), no aquí. Ver backend/docs/ROLE_HIERARCHY.md.
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
