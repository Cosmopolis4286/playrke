<?php

declare(strict_types=1);

namespace App\Services\Roles\Seller;

use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use Carbon\Carbon;

/**
 * Comisión progresiva por volumen mensual — ver
 * progreso/estrategia-producto-plan.md, Fase 10.
 *
 * Bloqueada explícitamente en el plan hasta calibrar tramos con datos
 * reales del ranking de vendedores (Fase 7). El usuario decidió construir
 * el MECANISMO ahora de todos modos, con umbrales de ejemplo marcados
 * como "a calibrar" en config/sellers.php — sin bloquear el código, pero
 * sin activar el efecto en pagos reales (`progressive_commission_enabled`
 * en false por defecto).
 *
 * Deliberadamente separado de CommissionCalculationService: este servicio
 * solo resuelve QUÉ bonus corresponde a un volumen dado; quien decide
 * SUMARLO a una venta real es CommissionCalculationService, y solo si el
 * flag está activo.
 */
final class SellerCommissionTierService
{
    public function isEnabled(): bool
    {
        return (bool) config('sellers.progressive_commission_enabled', false);
    }

    /**
     * Bonus porcentual (no la tasa final) correspondiente al volumen dado.
     * Ej. 2.5 significa "+2.5 puntos porcentuales" sobre la comisión base.
     */
    public function bonusPercentForVolume(float $monthlyVolume): float
    {
        $tiers = $this->tiers();
        $bonus = 0.0;

        foreach ($tiers as $tier) {
            if ($monthlyVolume >= $tier['min_volume']) {
                $bonus = (float) $tier['bonus_percent'];
            }
        }

        return $bonus;
    }

    /**
     * Volumen de venta del vendedor en el mes en curso (mismo cálculo que
     * SellerRankingService::buildRanking(), sin pasar por su caché de 90s
     * — este método se usa en el camino de cálculo de comisión de una
     * venta, que necesita el valor más al día posible).
     */
    public function monthlyVolumeForSeller(string $sellerUserId): float
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        return (float) BingoCardLot::where('seller_user_id', $sellerUserId)
            ->where('created_at', '>=', $startOfMonth)
            ->sum('sale_amount');
    }

    /**
     * Tramo actual y siguiente para un volumen dado — para mostrar
     * visibilidad ("te faltan $X para el próximo tramo") sin necesidad de
     * un endpoint nuevo; se consume desde SellerRankingService::getSellerStanding().
     */
    public function tierProgress(float $monthlyVolume): array
    {
        $tiers = $this->tiers();
        $current = $tiers[0] ?? ['min_volume' => 0, 'bonus_percent' => 0.0];
        $next = null;

        foreach ($tiers as $tier) {
            if ($monthlyVolume >= $tier['min_volume']) {
                $current = $tier;
                continue;
            }

            $next = $tier;
            break;
        }

        return [
            'current_bonus_percent' => (float) $current['bonus_percent'],
            'next_tier'             => $next ? [
                'min_volume'    => (float) $next['min_volume'],
                'bonus_percent' => (float) $next['bonus_percent'],
                'volume_needed' => max(0.0, round((float) $next['min_volume'] - $monthlyVolume, 2)),
            ] : null,
        ];
    }

    /**
     * @return array<int, array{min_volume: float, bonus_percent: float}>
     */
    private function tiers(): array
    {
        $tiers = config('sellers.progressive_commission_tiers', []);

        usort($tiers, fn ($a, $b) => $a['min_volume'] <=> $b['min_volume']);

        return $tiers;
    }
}
