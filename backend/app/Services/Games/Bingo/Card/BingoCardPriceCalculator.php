<?php

namespace App\Services\Games\Bingo\Card;

/**
 * Servicio de cálculo del precio unitario de un cartón de Bingo.
 *
 * Lógica extraída de BingoTrait::calculateCtnPrice().
 *
 * Responsabilidades:
 * - Calcular el precio del cartón en la moneda local del jugador.
 * - Soportar dos modos: precio base USD × tasa, o precio personalizado ÷ tasa.
 *
 * SIN estado. SIN dependencias externas. Completamente testeable en aislamiento.
 */
final class BingoCardPriceCalculator
{
    /**
     * Calcula el precio unitario del cartón en la moneda local.
     *
     * Modos de operación:
     *
     * **Modo base** (`$isCustomPrice = false`):
     *   Precio del cartón en USD de la sala × tasa de cambio.
     *   Ejemplo: USD 5 × tasa 36.5 = Bs 182.50
     *
     * **Modo personalizado** (`$isCustomPrice = true`):
     *   El precio ya está en moneda local (guardado en `bingo_card_prices`).
     *   Se normaliza: price ÷ exchangeRate → redondeado → devuelto.
     *   Permite expresar precios locales exactos independientes de la tasa flotante.
     *
     * @param float|int|string $price        Precio base (USD) o precio personalizado (moneda local).
     * @param bool             $isCustomPrice `true` si el precio proviene de `bingo_card_prices`.
     * @param float            $exchangeRate  Tasa de cambio activa del commerce (USD → moneda local).
     *                                        Ignorada en modo base si es 1; requerida en modo personalizado.
     *
     * @return float Precio final redondeado (2 decimales, o 3 si tiene parte fraccionaria).
     */
    public function calculate(
        float|int|string $price,
        bool             $isCustomPrice = false,
        float            $exchangeRate  = 1.0
    ): float {
        $price        = (float) $price;
        $exchangeRate = max($exchangeRate, 0.000001); // evitar división por cero

        if (!$isCustomPrice) {
            // Precio base USD × tasa → precio en moneda local
            return round($exchangeRate * $price, 2);
        }

        // Precio personalizado: normalizar y preservar decimales locales
        $normalized = round($price / $exchangeRate, 3);

        // Si es entero exacto, redondear a 2 decimales
        if (fmod($normalized, 1) == 0) {
            $normalized = round($normalized, 2);
        }

        return $normalized;
    }
}
