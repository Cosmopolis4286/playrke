<?php

namespace App\Services\ExchangeRate;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para obtener la tasa oficial USD/VES publicada
 * por el Banco Central de Venezuela (BCV) mediante scraping
 * de su página principal.
 *
 * Esta clase pertenece a la capa de infraestructura y encapsula
 * la lógica de acceso al sitio del BCV.
 */
final class BcvExchangeRateService
{
    /**
     * URL del sitio oficial del BCV.
     */
    private const BCV_URL = 'https://www.bcv.org.ve/';

    /**
     * Obtiene la tasa oficial USD/VES publicada por el BCV.
     *
     * @return float|null Tasa USD/VES o null si no se pudo obtener.
     */
    public function fetch(): ?float
    {
        try {
            $response = Http::timeout(10)
                ->withoutVerifying()
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
            Log::error('BcvExchangeRateService error: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Extrae la tasa USD/VES del HTML de la página principal del BCV.
     *
     * @param string $html
     * @return float|null
     */
    private function extractUsdRate(string $html): ?float
    {
        if (!preg_match('/USD<\/span>.*?<strong[^>]*>\s*([\d.,]+)\s*<\/strong>/si', $html, $matches)) {
            return null;
        }

        // El BCV usa coma como separador decimal y punto como separador de miles.
        $value = str_replace('.', '', $matches[1]);
        $value = str_replace(',', '.', $value);

        return is_numeric($value) ? (float) $value : null;
    }
}
