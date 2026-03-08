<?php

namespace App\Services\Games\Bingo\Card;

/**
 * Servicio de cálculo del precio unitario de un cartón de Bingo.
 *
 * Responsabilidades:
 * - Calcular el precio del cartón en la moneda local del jugador.
 * - Soportar dos modos: precio base (USD × tasa) o precio personalizado (ya en moneda local).
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
     *   Precio base de la sala en USD × tasa de cambio del commerce.
     *   Ejemplo: USD 5 × tasa 36.50 = Bs 182.50
     *
     * **Modo personalizado** (`$isCustomPrice = true`):
     *   El precio ya está en moneda local, guardado directamente en `bingo_card_prices`.
     *   Se devuelve tal cual, redondeado a 2 decimales.
     *   La tasa de cambio no interviene en este modo.
     *   Ejemplo: Bs 120 → Bs 120.00
     *
     * @param float|int|string $price        Precio base en USD (modo base) o precio en moneda local (modo personalizado).
     * @param bool             $isCustomPrice `true` si el precio proviene de `bingo_card_prices`.
     * @param float            $exchangeRate  Tasa de cambio USD → moneda local. Solo se usa en modo base.
     *
     * @return float Precio final redondeado a 2 decimales.
     */
    public function calculate(
        float|int|string $price,
        bool             $isCustomPrice = false,
        float            $exchangeRate  = 1.0
    ): float {
        $price = (float) $price;

        if ($isCustomPrice) {
            // El precio ya está expresado en moneda local → devolver directo
            return round($price, 2);
        }

        // Precio base USD × tasa de cambio → precio en moneda local
        return round($exchangeRate * $price, 2);
    }
}
