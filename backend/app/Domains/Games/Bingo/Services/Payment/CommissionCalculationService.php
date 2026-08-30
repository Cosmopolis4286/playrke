<?php

namespace App\Domains\Games\Bingo\Services\Payment;

use App\Models\Roles\Seller;
use App\Models\Pivots\Roles\Seller\SellerRoomLimits;
use App\Domains\Games\Bingo\Contracts\CommissionCalculationInterface;
use App\Services\Roles\Seller\SellerCommissionTierService;
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
    public function __construct(
        private readonly SellerCommissionTierService $commissionTierService = new SellerCommissionTierService(),
    ) {}

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

            $effectiveRate = (float) $commnSeller + $this->progressiveBonusPercent($sellerId);

            return round($saleAmount * ($effectiveRate / 100), 2);
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
     * Bonus adicional por comisión progresiva (Fase 10, ver
     * progreso/estrategia-producto-plan.md) — 0 mientras
     * `sellers.progressive_commission_enabled` esté en false (default), para
     * no alterar ningún pago real hasta activarlo a propósito con umbrales
     * ya calibrados.
     */
    private function progressiveBonusPercent(string $sellerId): float
    {
        if (!$this->commissionTierService->isEnabled()) {
            return 0.0;
        }

        $sellerUserId = Seller::getValueById($sellerId, 'user_id');

        if (!$sellerUserId) {
            return 0.0;
        }

        $monthlyVolume = $this->commissionTierService->monthlyVolumeForSeller((string) $sellerUserId);

        return $this->commissionTierService->bonusPercentForVolume($monthlyVolume);
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
