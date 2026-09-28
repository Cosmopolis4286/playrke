<?php

namespace App\Services\Roles;

use App\Exceptions\Domain\Roles\CommissionHierarchyException;
use App\Models\Roles\Commerce;
use App\Models\Roles\Seller;
use App\Models\Roles\Subsidiary;

/**
 * Topes de comisión de la red comercial: vendedor ≤ agencia ≤ comercializadora
 * (para un vendedor directo: vendedor ≤ comercializadora).
 *
 * Es la precondición del reparto en cascada (CommissionCalculationService::
 * networkSplit()): cada nivel superior cobra la diferencia con el inferior, así
 * que un hijo con tasa mayor que su padre dejaría al padre sin margen. Se
 * valida en cada punto que cambia una tasa o mueve un nodo del árbol:
 * alta/edición/reasignación de vendedor, alta/edición/traslado de agencia y
 * edición de comercializadora.
 *
 * Solo cuenta hijos no eliminados (status != DELETED): un vendedor borrado no
 * vende y no debe bloquear bajar la comisión de su padre.
 */
final class CommissionHierarchyGuard
{
    /**
     * Tasa de un vendedor contra su padre directo: la agencia si tiene, si no
     * la comercializadora.
     */
    public function assertSellerRate(float $rate, ?string $subsidiaryId, int|string $commerceUserId): void
    {
        $max = $subsidiaryId
            ? Subsidiary::whereKey($subsidiaryId)->value('commission')
            : Commerce::where('user_id', $commerceUserId)->value('commission');

        if ($max !== null && $rate > (float) $max) {
            throw CommissionHierarchyException::aboveParent($rate, (float) $max);
        }
    }

    /**
     * Tasa de una agencia: ≤ su comercializadora y ≥ cada uno de sus vendedores.
     * $subsidiaryId null en el alta (aún no tiene vendedores).
     */
    public function assertSubsidiaryRate(float $rate, int|string $commerceId, ?string $subsidiaryId = null): void
    {
        $max = Commerce::whereKey($commerceId)->value('commission');

        if ($max !== null && $rate > (float) $max) {
            throw CommissionHierarchyException::aboveParent($rate, (float) $max);
        }

        if ($subsidiaryId === null) {
            return;
        }

        $min = Seller::where('subsidiary_id', $subsidiaryId)
            ->where('status', '!=', 'DELETED')
            ->max('commission');

        if ($min !== null && $rate < (float) $min) {
            throw CommissionHierarchyException::belowChildren($rate, (float) $min);
        }
    }

    /**
     * Tasa de una comercializadora: ≥ cada agencia y cada vendedor directo.
     */
    public function assertCommerceRate(float $rate, Commerce $commerce): void
    {
        $maxSubsidiary = Subsidiary::where('commerce_id', $commerce->id)
            ->where('status', '!=', 'DELETED')
            ->max('commission');

        $maxDirectSeller = Seller::where('manager_user_id', $commerce->user_id)
            ->whereNull('subsidiary_id')
            ->where('status', '!=', 'DELETED')
            ->max('commission');

        $min = max((float) ($maxSubsidiary ?? 0), (float) ($maxDirectSeller ?? 0));

        if ($rate < $min) {
            throw CommissionHierarchyException::belowChildren($rate, $min);
        }
    }
}
