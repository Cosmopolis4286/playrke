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
        return round($saleAmount * ($this->sellerCommissionRate($sellerId, $roomId) / 100), 2);
    }

    /**
     * Tasa (%) de comisión EFECTIVA del vendedor en una sala: comisión
     * especial de la sala (sellers_room_limits) o, si no hay, su comisión
     * base — más el bono progresivo por volumen del mes.
     *
     * Única fuente de verdad del porcentaje: la usa la venta
     * (calculateSellerCommission) y la vista previa del formulario de
     * generar cartones (BingoCardGenerateService::getCardPrice) — antes la
     * vista previa mostraba solo la comisión base y el vendedor veía un
     * "Mi Comisión" distinto del que se registraba.
     *
     * Ya no se traga errores devolviendo 0: una comisión mal calculada es
     * dinero mal registrado. El error se loguea y se propaga, y la venta
     * (dentro de su transacción) se revierte completa.
     */
    public function sellerCommissionRate(?string $sellerId, ?string $roomId = null): float
    {
        if (!$sellerId) {
            return 0.0;
        }

        try {
            $commnSeller = SellerRoomLimits::getGameRoomValueBySeller('BINGO', $sellerId, $roomId, 'commission')
                ?? Seller::getValueById($sellerId, 'commission');

            if (!$commnSeller) {
                return 0.0;
            }

            return (float) $commnSeller + $this->progressiveBonusPercent($sellerId);
        } catch (\Throwable $e) {
            Log::error('Error al calcular comisión del vendedor: ' . $e->getMessage(), [
                'sellerId' => $sellerId,
                'roomId'   => $roomId,
            ]);

            throw $e;
        }
    }

    /**
     * Reparto en cascada de la comisión de la red comercial (Comercializadora
     * → Agencia → Vendedor) para una venta hecha por un vendedor.
     *
     * Cada nivel tiene una tasa sobre la venta: vendedor X (efectiva, ver
     * sellerCommissionRate()), agencia Y (subsidiaries.commission) y
     * comercializadora Z (commerces.commission). Cada nivel superior cobra la
     * DIFERENCIA con el nivel inferior — modelo estándar de override:
     *
     *   vendedor         = X
     *   agencia          = max(0, Y − X)                 (solo si tiene agencia)
     *   comercializadora = max(0, Z − max(X, Y))         (Y = 0 si es directo)
     *
     * La comisión total de la red es max(X, Y, Z). Los topes X ≤ Y ≤ Z se
     * validan al configurar (CommissionHierarchyGuard); aun si se rompen (bono
     * progresivo o comisión especial de sala por encima del padre), el max(0, …)
     * garantiza que ningún nivel cobre negativo ni se cuente dos veces.
     *
     * La agencia/comercializadora se resuelven al momento de la venta y se
     * devuelven para guardarse como snapshot en el lote.
     */
    public function networkSplit(float $saleAmount, ?string $sellerId, ?string $roomId = null): array
    {
        $split = [
            'seller_rate' => 0.0, 'subsidiary_rate' => 0.0, 'commerce_rate' => 0.0,
            'seller_amount' => 0.0, 'subsidiary_amount' => 0.0, 'commerce_amount' => 0.0,
            'subsidiary_id' => null, 'commerce_id' => null,
        ];

        if (!$sellerId) {
            return $split;
        }

        $seller = Seller::with(['subsidiary:id,commerce_id,commission', 'subsidiary.commerce:id,user_id,commission', 'managerCommerce:id,user_id,commission'])
            ->find($sellerId, ['id', 'manager_user_id', 'subsidiary_id']);

        $sellerRate = $this->sellerCommissionRate($sellerId, $roomId);
        $subsidiary = $seller?->subsidiary_id ? $seller->subsidiary : null;
        $commerce   = $seller?->resolveCommerce();

        $subsidiaryRate = $subsidiary ? max(0.0, (float) $subsidiary->commission - $sellerRate) : 0.0;
        $floor          = max($sellerRate, $subsidiary ? (float) $subsidiary->commission : 0.0);
        $commerceRate   = $commerce ? max(0.0, (float) $commerce->commission - $floor) : 0.0;

        $amount = fn (float $rate) => round($saleAmount * ($rate / 100), 2);

        return [
            'seller_rate'       => $sellerRate,
            'subsidiary_rate'   => $subsidiaryRate,
            'commerce_rate'     => $commerceRate,
            'seller_amount'     => $amount($sellerRate),
            'subsidiary_amount' => $amount($subsidiaryRate),
            'commerce_amount'   => $amount($commerceRate),
            'subsidiary_id'     => $subsidiary ? (string) $subsidiary->id : null,
            'commerce_id'       => $commerce ? (string) $commerce->id : null,
        ];
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
