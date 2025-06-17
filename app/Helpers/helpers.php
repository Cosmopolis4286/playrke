<?php

if (!function_exists('truncate')) {
    /**
     * Truncar un número sin redondear.
     */
    function truncate($number, $decimals = 2)
    {
        $factor = pow(10, $decimals);
        return floor($number * $factor) / $factor;
    }
}
