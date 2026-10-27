<?php

// Funciones de cálculo, truncar, redondeos, porcentajes

if (! function_exists('truncate')) {
    /**
     * Truncar un número sin redondear.
     */
    function truncate($number, $decimals = 2)
    {
        $factor = 10 ** $decimals;

        return floor($number * $factor) / $factor;
    }
}

if (! function_exists('percent')) {
    /**
     * Calcular porcentaje.
     */
    function percent($value, $total)
    {
        return $total == 0 ? 0 : ($value / $total) * 100;
    }
}
