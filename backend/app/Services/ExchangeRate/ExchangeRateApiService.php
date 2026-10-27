<?php

namespace App\Services\ExchangeRate;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Implementación de proveedor de tasas de cambio que obtiene los datos
 * desde una API externa vía HTTP.
 *
 * Esta clase pertenece a la capa de infraestructura y encapsula la lógica
 * de acceso a la API remota.
 */
final class ExchangeRateApiService
{
    /**
     * URL de la API externa para obtener las tasas de cambio.
     *
     * Antes apuntaba a smcvenezuela.xyz/dolarsmcbot/binance-api.php (bot de
     * Telegram no oficial) — el dominio dejó de resolver (NXDOMAIN,
     * detectado 2026-09-29), dejando "Consultar" muerto sin ningún error
     * visible más que el mensaje genérico "No hay tasas disponibles".
     * open.er-api.com es gratuita, sin API key, y cubre BRL/COP/VES (las 3
     * monedas FIAT activas del sistema) en una sola llamada con base USD.
     */
    private const API_URL = 'https://open.er-api.com/v6/latest/USD';

    private const CACHE_KEY = 'exchange_rate:external_api:binance';

    /**
     * TTL del caché en segundos. Sin caché, cada apertura del modal
     * "Consultar" (uno por admin/comercio/customer) y cada corrida del
     * cronjob horario golpeaban en vivo un proveedor de terceros no
     * oficial (bot de Telegram) sin ningún colchón ante picos de uso
     * concurrente o caídas del proveedor. 60s es corto para el usuario
     * (percibe la tasa como "en vivo") pero absorbe ráfagas de requests.
     */
    private const CACHE_TTL_SECONDS = 60;

    /**
     * Realiza la consulta a la API externa y devuelve las tasas de cambio.
     *
     * @param int|null $userId ID del usuario (opcional)
     *
     * @return array|null retorna un arreglo con las tasas si la llamada fue exitosa,
     *                    o null si ocurrió algún error
     */
    public function fetch(?int $userId = null) : ?array
    {
        $cached = Cache::get(self::CACHE_KEY);
        if ($cached !== null) {
            return $cached;
        }

        try {
            // Realiza una petición HTTP GET con un timeout de 5 segundos
            $response = Http::timeout(5)->get(self::API_URL);

            // Si la respuesta no es exitosa (status >= 400), lanza excepción
            if ($response->failed()) {
                throw new RuntimeException('API request failed with status ' . $response->status());
            }

            // open.er-api.com anida las tasas bajo "rates" (formato
            // {"result":"success","base_code":"USD","rates":{"BRL":5.21,...}})
            // — el resto del dominio espera un array plano iso_code => valor
            // (mismo shape que devolvía el proveedor anterior), así que se
            // desenvuelve acá, en el único punto que conoce el formato crudo
            // de la API externa.
            $rates = $response->json('rates');

            if (empty($rates) || ! is_array($rates)) {
                throw new RuntimeException('La API externa no devolvió tasas válidas.');
            }

            // Solo se cachean respuestas válidas — un fallo NO se cachea, para
            // que el próximo fetch() pueda reintentar de inmediato en vez de
            // esperar el TTL completo sirviendo null.
            Cache::put(self::CACHE_KEY, $rates, self::CACHE_TTL_SECONDS);

            return $rates;
        } catch (Throwable $e) {
            // Registra el error para diagnóstico
            Log::error('ExchangeRateApiService error: ' . $e->getMessage());

            // Retorna null para indicar fallo sin interrumpir la ejecución
            return null;
        }
    }
}
