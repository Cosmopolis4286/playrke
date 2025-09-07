<?php

namespace App\Services\Games\Bingo\Cartons;

use App\Models\{BingoCtnPrice, BingoCtnPriceDrafts};

class CartonPriceService
{
    /**
     * Asocia los precios de monedas a la sala.
     */
    public function associateCtnPrices(int $automaticBingoId, int $roomId): void
    {
        $draft = BingoCtnPriceDrafts::getByAutomaticRoom($automaticBingoId);

        if (!$draft) {
            return;
        }

        $currencies = $draft->currencies ?? [];
        $prices = $draft->card_prices ?? [];

        foreach ($currencies as $index => $currency) {
            BingoCtnPrice::updateOrCreate(
                ['room' => $roomId, 'currency' => $currency],
                [
                    'creator_user_id' => $draft->creator_user,
                    'card_price' => $prices[$index] ?? 0
                ]
            );
        }

        $draft->update(['status' => 'FINISHED']);
    }
}
