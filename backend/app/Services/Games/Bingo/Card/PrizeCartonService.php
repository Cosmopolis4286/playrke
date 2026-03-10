<?php

namespace App\Services\Games\Bingo\Card;

use App\Models\{BingoCardPrice, BingoCardPriceAutomatic};
use Illuminate\Support\Facades\Log;

class PrizeCartonService
{
    /**
     * Registra los precios de cartones para una sala.
     *
     * @param int $roomId
     * @return bool Retorna true si los precios se registraron correctamente.
     * @throws \Exception Si los datos de draft son inconsistentes o hay un error de base de datos.
     */
    public function registerCtnPrices(int $roomId): bool
    {
        try {
            // Obtener draft de precios para la sala
            $draft = BingoCardPriceAutomatic::getByRoom($roomId, ['creator_user_id', 'currencies', 'card_prices', 'status']);

            if (!$draft) {
                Log::info("No se encontró draft de precios para sala ID {$roomId}");
                return false;
            }

            $currencies = (array) $draft->currencies;
            $prices = (array) $draft->card_prices;

            // Validar integridad de datos
            if (count($currencies) !== count($prices)) {
                throw new \Exception("Datos inconsistentes: la cantidad de monedas no coincide con la de precios. Sala ID {$roomId}");
            }

            // Registrar precios usando updateOrCreate para evitar duplicados
            foreach ($currencies as $index => $currency) {
                BingoCardPrice::updateOrCreate(
                    ['room' => $roomId, 'currency' => $currency],
                    [
                        'creator_user_id' => $draft->creator_user,
                        'card_price' => $prices[$index],
                    ]
                );
            }

            // Marcar draft como completado
            $draft->update(['status' => 'FINISHED']);

            return true;
        } catch (\Exception $e) {
            Log::error("Error al registrar precios de cartones para sala ID {$roomId}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
