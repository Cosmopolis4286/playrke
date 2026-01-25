<?php

namespace App\Contracts\ExchangeRate;

/**
 * Interfaz para proveedores de tasas de cambio.
 *
 * Define el contrato que deben implementar las clases
 * que provean las tasas de cambio, sin importar la fuente
 * (API externa, base de datos, archivo, etc.).
 */
interface ExchangeRateProviderInterface
{
    /**
     * Obtiene las tasas de cambio.
     *
     * @return array|null Retorna un arreglo con las tasas de cambio o null si no se pudo obtener.
     */
    public function fetch(): ?array;
}
