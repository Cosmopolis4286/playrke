<?php

namespace App\Services\Games\Bingo\Cartons;

use App\Models\{BingoCtnPrice, BingoCtnPriceDrafts};

class CartonPriceService
{
    /**
     * Asocia los precios de monedas a la sala.
     */
    public function associateCtnPrices(int $automaticBingoID, int $roomId): void
    {
        $draft = BingoCtnPriceDrafts::getByAutomaticRoom($automaticBingoID);

        if (!$draft) {
            return;
        }

        $coins = $draft->coins ?? [];
        $prices = $draft->ctn_prices ?? [];

        foreach ($coins as $index => $coin) {
            BingoCtnPrice::updateOrCreate(
                ['room' => $roomId, 'coin' => $coin],
                [
                    'creator_user_id' => $draft->creator_user,
                    'ctn_price' => $prices[$index] ?? 0
                ]
            );
        }

        $draft->update(['status' => 'FINISHED']);
    }
}
