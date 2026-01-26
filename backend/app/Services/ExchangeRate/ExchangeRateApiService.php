<?php

namespace App\Services\ExchangeRate;

use App\Contracts\ExchangeRate\ExchangeRateProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Implementación de proveedor de tasas de cambio que obtiene los datos
 * desde una API externa vía HTTP.
 *
 * Esta clase pertenece a la capa de infraestructura y encapsula la lógica
 * de acceso a la API remota.
 */
final class ExchangeRateApiService implements ExchangeRateProviderInterface
{
    /**
     * URL de la API externa para obtener las tasas de cambio.
     */
    private const API_URL = 'https://smcvenezuela.xyz/dolarsmcbot/binance-api.php';

    /**
     * Realiza la consulta a la API externa y devuelve las tasas de cambio.
     *
     * @param int|null $userId ID del usuario (opcional)
     * @return array|null Retorna un arreglo con las tasas si la llamada fue exitosa,
     *                    o null si ocurrió algún error.
     */
    public function fetch(?int $userId = null): ?array
    {
        try {
            // Realiza una petición HTTP GET con un timeout de 5 segundos
            $response = Http::timeout(5)->get(self::API_URL);

            // Si la respuesta no es exitosa (status >= 400), lanza excepción
            if ($response->failed()) {
                throw new \RuntimeException('API request failed with status ' . $response->status());
            }

            // Retorna el contenido decodificado como arreglo asociativo
            return $response->json();
        } catch (\Throwable $e) {
            // Registra el error para diagnóstico
            Log::error('ExchangeRateApiService error: ' . $e->getMessage());

            // Retorna null para indicar fallo sin interrumpir la ejecución
            return null;
        }
    }
}
