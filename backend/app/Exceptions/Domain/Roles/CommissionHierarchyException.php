<?php

namespace App\Exceptions\Domain\Roles;

use App\Exceptions\Domain\DomainException;

/**
 * Topes de comisión de la red comercial violados (ver
 * App\Services\Roles\CommissionHierarchyGuard): vendedor ≤ agencia ≤
 * comercializadora. Con los topes rotos el reparto en cascada
 * (CommissionCalculationService::networkSplit()) dejaría a un nivel superior
 * sin margen.
 */
final class CommissionHierarchyException extends DomainException
{
    /** La comisión excede la de su nivel superior (agencia o comercializadora). */
    public static function aboveParent(float $rate, float $max) : self
    {
        return new self('COMMISSION_ABOVE_PARENT', 422, null, [
            'rate' => number_format($rate, 2),
            'max' => number_format($max, 2),
        ]);
    }

    /** La comisión queda por debajo de la de algún nivel inferior. */
    public static function belowChildren(float $rate, float $min) : self
    {
        return new self('COMMISSION_BELOW_CHILDREN', 422, null, [
            'rate' => number_format($rate, 2),
            'min' => number_format($min, 2),
        ]);
    }

    /**
     * Los vendedores de una agencia (de paso) quedarían por encima de la
     * comercializadora — ej. al trasladar la agencia a una con tasa menor.
     */
    public static function childrenAboveParent(float $rate, float $max) : self
    {
        return new self('COMMISSION_CHILDREN_ABOVE_PARENT', 422, null, [
            'rate' => number_format($rate, 2),
            'max' => number_format($max, 2),
        ]);
    }
}
