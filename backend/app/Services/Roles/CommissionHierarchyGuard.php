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
 * que un hijo con tasa mayor que su padre dejaría al padre sin margen.
 *
 * Agencia "sin comisión" (commission 0 — el toggle has_commission de
 * AdminSubsidiaryForm): es una agencia de paso que no cobra override, NO un
 * tope de 0% para sus vendedores. Sus vendedores quedan topados por la
 * comercializadora — mismo resultado que networkSplit() (agencia max(0, 0−X)
 * = 0, comercializadora Z − X).
 *
 * Los callers solo validan cuando la tasa o el padre CAMBIAN de verdad (no
 * cuando el form reenvía el mismo valor): un registro legado que ya violaba un
 * tope sigue siendo editable en todo lo demás, y el mensaje de error indica el
 * rango válido cuando sí se intenta cambiar. Los registros legados se detectan
 * con findViolations() (comando commissions:audit-hierarchy).
 *
 * Solo cuenta hijos no eliminados (status != DELETED): un vendedor borrado no
 * vende y no debe bloquear bajar la comisión de su padre.
 */
final class CommissionHierarchyGuard
{
    /** Tolerancia para comparar tasas decimal(…, 2) leídas como float. */
    private const EPSILON = 0.001;

    public static function rateChanged(mixed $new, mixed $current): bool
    {
        return $new !== null && $new !== ''
            && abs((float) $new - (float) ($current ?? 0)) > self::EPSILON;
    }

    /**
     * Tope de un vendedor: la tasa de su agencia si la agencia cobra comisión
     * propia; si no (vendedor directo o agencia de paso), la de su
     * comercializadora.
     */
    public function sellerCap(?string $subsidiaryId, int|string|null $commerceUserId): ?float
    {
        if ($subsidiaryId) {
            $subsidiary = Subsidiary::with('commerce:id,commission')->find($subsidiaryId, ['id', 'commerce_id', 'commission']);

            if ($subsidiary && (float) $subsidiary->commission > self::EPSILON) {
                return (float) $subsidiary->commission;
            }

            if ($subsidiary?->commerce) {
                return (float) $subsidiary->commerce->commission;
            }
        }

        $commerceRate = $commerceUserId !== null
            ? Commerce::where('user_id', $commerceUserId)->value('commission')
            : null;

        return $commerceRate !== null ? (float) $commerceRate : null;
    }

    public function assertSellerRate(float $rate, ?string $subsidiaryId, int|string|null $commerceUserId): void
    {
        $max = $this->sellerCap($subsidiaryId, $commerceUserId);

        if ($max !== null && $rate > $max + self::EPSILON) {
            throw CommissionHierarchyException::aboveParent($rate, $max);
        }
    }

    /**
     * Tasa de una agencia: ≤ su comercializadora; si cobra comisión propia
     * (> 0), además ≥ cada uno de sus vendedores. Sus vendedores siempre deben
     * quedar ≤ la comercializadora (cubre trasladar una agencia de paso a una
     * comercializadora con tasa menor). $subsidiaryId null en el alta.
     */
    public function assertSubsidiaryRate(float $rate, int|string $commerceId, ?string $subsidiaryId = null): void
    {
        $commerceRate = Commerce::whereKey($commerceId)->value('commission');
        $max = $commerceRate !== null ? (float) $commerceRate : null;

        if ($max !== null && $rate > $max + self::EPSILON) {
            throw CommissionHierarchyException::aboveParent($rate, $max);
        }

        if ($subsidiaryId === null) {
            return;
        }

        $maxSeller = Seller::where('subsidiary_id', $subsidiaryId)
            ->where('status', '!=', 'DELETED')
            ->max('commission');

        if ($maxSeller === null) {
            return;
        }

        if ($rate > self::EPSILON && $rate + self::EPSILON < (float) $maxSeller) {
            throw CommissionHierarchyException::belowChildren($rate, (float) $maxSeller);
        }

        if ($max !== null && (float) $maxSeller > $max + self::EPSILON) {
            throw CommissionHierarchyException::childrenAboveParent((float) $maxSeller, $max);
        }
    }

    /**
     * Tasa de una comercializadora: ≥ cada agencia y cada vendedor de su red
     * (directos y de agencia — los de una agencia de paso cuelgan de ella).
     */
    public function assertCommerceRate(float $rate, Commerce $commerce): void
    {
        $min = $this->maxNetworkRate($commerce);

        if ($rate + self::EPSILON < $min) {
            throw CommissionHierarchyException::belowChildren($rate, $min);
        }
    }

    private function maxNetworkRate(Commerce $commerce): float
    {
        $subsidiaryIds = Subsidiary::where('commerce_id', $commerce->id)
            ->where('status', '!=', 'DELETED')
            ->pluck('id');

        $maxSubsidiary = Subsidiary::whereIn('id', $subsidiaryIds)->max('commission');

        $maxSeller = Seller::where('status', '!=', 'DELETED')
            ->where(fn ($q) => $q
                ->where(fn ($d) => $d->whereNull('subsidiary_id')->where('manager_user_id', $commerce->user_id))
                ->orWhereIn('subsidiary_id', $subsidiaryIds))
            ->max('commission');

        return max((float) ($maxSubsidiary ?? 0), (float) ($maxSeller ?? 0));
    }

    /**
     * Registros existentes que violan algún tope — mismas reglas que los
     * assert*(). Para el comando commissions:audit-hierarchy.
     *
     * @return list<array{type: string, id: string, name: string, rate: float, max: float, parent: string}>
     */
    public function findViolations(): array
    {
        $violations = [];

        $subsidiaries = Subsidiary::with('commerce:id,name,commission')
            ->where('status', '!=', 'DELETED')
            ->get(['id', 'name', 'commerce_id', 'commission']);

        foreach ($subsidiaries as $subsidiary) {
            if ($subsidiary->commerce && (float) $subsidiary->commission > (float) $subsidiary->commerce->commission + self::EPSILON) {
                $violations[] = [
                    'type'   => 'agencia',
                    'id'     => (string) $subsidiary->id,
                    'name'   => $subsidiary->name,
                    'rate'   => (float) $subsidiary->commission,
                    'max'    => (float) $subsidiary->commerce->commission,
                    'parent' => 'comercializadora ' . $subsidiary->commerce->name,
                ];
            }
        }

        Seller::with(['subsidiary:id,name,commerce_id,commission', 'subsidiary.commerce:id,name,commission', 'managerCommerce:id,user_id,name,commission'])
            ->where('status', '!=', 'DELETED')
            ->chunkById(500, function ($sellers) use (&$violations) {
                foreach ($sellers as $seller) {
                    $agencyCharges = $seller->subsidiary && (float) $seller->subsidiary->commission > self::EPSILON;
                    $commerce = $seller->resolveCommerce();

                    [$max, $parent] = $agencyCharges
                        ? [(float) $seller->subsidiary->commission, 'agencia ' . $seller->subsidiary->name]
                        : [$commerce ? (float) $commerce->commission : null, $commerce ? 'comercializadora ' . $commerce->name : null];

                    if ($max !== null && (float) $seller->commission > $max + self::EPSILON) {
                        $violations[] = [
                            'type'   => 'vendedor',
                            'id'     => (string) $seller->id,
                            'name'   => $seller->name,
                            'rate'   => (float) $seller->commission,
                            'max'    => $max,
                            'parent' => $parent,
                        ];
                    }
                }
            });

        return $violations;
    }
}
