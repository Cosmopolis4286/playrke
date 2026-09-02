<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Services;

use App\Domains\Marketplace\Models\Listing;

/**
 * Resuelve la comisión de plataforma sobre una venta. Patrón inspirado en
 * CommissionCalculationService/SellerCommissionTierService (Bingo) pero sin
 * acoplarse a Seller/Bingo — ver MARKETPLACE_ARCHITECTURE_AUDIT.md sección 20.
 * El fee siempre se registra en el ledger por el caller (EscrowService, Fase 3);
 * este servicio solo calcula el monto, nunca mueve dinero.
 */
final class MarketplaceFeeService
{
    /**
     * @return float Monto de comisión (no porcentaje) sobre $amount.
     */
    public function calculate(Listing $listing, float $amount): float
    {
        $percent = $this->resolvePercent($listing);

        return round($amount * ($percent / 100), 2);
    }

    public function resolvePercent(Listing $listing): float
    {
        $category = $listing->playerAsset?->assetDefinition?->category;

        $byCategory = config('marketplace.fee_by_category', []);

        if ($category !== null && array_key_exists($category, $byCategory)) {
            return (float) $byCategory[$category];
        }

        return (float) config('marketplace.default_fee_percent', 5.0);
    }
}
