<?php

namespace App\Services\ExchangeRate;

use Illuminate\Support\Facades\Log;

/**
 * Servicio de dominio para gestión de tasas de cambio.
 *
 * Responsabilidades:
 * - Exponer casos de uso del dominio
 * - Orquestar servicios especializados
 * - Mantener a los controllers desacoplados
 *
 * NO contiene lógica HTTP ni infraestructura.
 */
final class ExchangeRateApiService
{
    public function fetch(): ?array
    {
        $url = 'https://smcvenezuela.xyz/dolarsmcbot/binance-api.php';

        try {
            $response = file_get_contents($url);

            if ($response === false) {
                throw new \Exception('No response from API');
            }

            return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Log::error('ExchangeRateApiService error: ' . $e->getMessage());
            return null;
        }
    }
}
