<?php

namespace App\Services\ExchangeRate;

use DomainException;

/**
 * Domain service encargado de cálculos relacionados con variaciones
 * porcentuales de tipos de cambio.
 */
final class ExchangeRateDomainService
{
    /**
     * Calcula la variación porcentual entre un monto anterior y uno nuevo.
     *
     * Fórmula:
     *   ((nuevo * 100) / anterior) - 100
     *
     * Ejemplo:
     *   old = 100
     *   new = 110
     *   resultado = 10.00
     *
     * @param float $old Monto anterior (base del cálculo). Debe ser mayor a 0.
     * @param float $new Monto nuevo a comparar.
     *
     * @return float Variación porcentual redondeada a 2 decimales.
     *
     * @throws DomainException Si el monto anterior es menor o igual a cero.
     */
    public function calculatePercentage(float $old, float $new): float
    {
        if ($old <= 0) {
            throw new DomainException('Monto anterior inválido');
        }

        return round((($new * 100) / $old) - 100, 2);
    }
}
