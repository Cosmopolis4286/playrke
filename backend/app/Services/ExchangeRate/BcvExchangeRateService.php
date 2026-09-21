<?php

namespace App\Services\ExchangeRate;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para obtener la tasa oficial USD/VES publicada
 * por el Banco Central de Venezuela (BCV).
 *
 * Intenta primero el scraping de la página oficial del BCV y,
 * si falla, recurre a la API gratuita de DolarApi (ve.dolarapi.com)
 * como respaldo.
 *
 * Esta clase pertenece a la capa de infraestructura y encapsula
 * la lógica de acceso a las fuentes externas.
 */
final class BcvExchangeRateService
{
    /**
     * URL del sitio oficial del BCV.
     */
    private const BCV_URL = 'https://www.bcv.org.ve/';

    /**
     * URL de la API de respaldo (DolarApi - tasa oficial Venezuela).
     */
    private const FALLBACK_API_URL = 'https://ve.dolarapi.com/v1/dolares/oficial';

    /**
     * TTL del caché en segundos. El BCV publica su tasa oficial UNA vez
     * al día — sin caché, cada apertura del selector VES o corrida del
     * cronjob hacía scraping en vivo (o golpeaba el fallback) sin ningún
     * colchón. 5 minutos es generoso para el caso de uso real y absorbe
     * por completo las ráfagas de requests concurrentes.
     */
    private const CACHE_TTL_SECONDS = 300;

    private const CACHE_KEY = 'exchange_rate:external_api:bcv';

    /**
     * Obtiene la tasa oficial USD/VES publicada por el BCV.
     *
     * @return float|null Tasa USD/VES o null si no se pudo obtener de ninguna fuente.
     */
    public function fetch(): ?float
    {
        $cached = Cache::get(self::CACHE_KEY);
        if ($cached !== null) {
            return (float) $cached;
        }

        $rate = $this->fetchFromBcv() ?? $this->fetchFromFallbackApi();

        // No se cachea null — un fallo de ambas fuentes no debe bloquear el
        // próximo intento hasta que expire el TTL completo.
        if ($rate !== null) {
            Cache::put(self::CACHE_KEY, $rate, self::CACHE_TTL_SECONDS);
        }

        return $rate;
    }

    /**
     * Obtiene la tasa USD/VES mediante scraping de la página principal del BCV.
     *
     * @return float|null
     */
    private function fetchFromBcv(): ?float
    {
        try {
            $response = Http::timeout(8)
                ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')
                ->get(self::BCV_URL);

            if ($response->failed()) {
                throw new \RuntimeException('Solicitud al BCV falló con estado ' . $response->status());
            }

            $rate = $this->extractUsdRate($response->body());

            if ($rate === null) {
                throw new \RuntimeException('No se pudo extraer la tasa USD del BCV.');
            }

            return $rate;
        } catch (\Throwable $e) {
            Log::error('BcvExchangeRateService (BCV) error: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Obtiene la tasa oficial USD/VES desde la API de respaldo (DolarApi).
     *
     * @return float|null
     */
    private function fetchFromFallbackApi(): ?float
    {
        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->get(self::FALLBACK_API_URL);

            if ($response->failed()) {
                throw new \RuntimeException('Solicitud a la API de respaldo falló con estado ' . $response->status());
            }

            $rate = $response->json('promedio');

            if (!is_numeric($rate)) {
                throw new \RuntimeException('La API de respaldo no devolvió una tasa válida.');
            }

            return (float) $rate;
        } catch (\Throwable $e) {
            Log::error('BcvExchangeRateService (fallback) error: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Extrae la tasa USD/VES del HTML de la página principal del BCV.
     *
     * Busca primero dentro del bloque con id="dolar" (más robusto ante
     * cambios en el orden de las monedas) y, si no lo encuentra, recurre
     * al patrón genérico basado en la etiqueta "USD".
     *
     * @param string $html
     * @return float|null
     */
    private function extractUsdRate(string $html): ?float
    {
        $value = null;

        if (preg_match('/id="dolar".*?<\/div>\s*<\/div>\s*<\/div>/si', $html, $block)) {
            if (preg_match('/<strong[^>]*>\s*([\d.,]+)\s*<\/strong>/si', $block[0], $matches)) {
                $value = $matches[1];
            }
        }

        if ($value === null && preg_match('/USD<\/span>.*?<strong[^>]*>\s*([\d.,]+)\s*<\/strong>/si', $html, $matches)) {
            $value = $matches[1];
        }

        if ($value === null) {
            return null;
        }

        // El BCV usa coma como separador decimal y punto como separador de miles.
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        return is_numeric($value) ? (float) $value : null;
    }
}
