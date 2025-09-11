<?php

namespace App\Services\Games\Bingo\Cards;

use App\Models\{BingoCardPrice, BingoCardPriceDraft};

class CartonPriceService
{
    /**
     * Asocia los precios de monedas a la sala.
     */
    public function associateCtnPrices(int $automaticBingoId, int $roomId): void
    {
        $draft = BingoCardPriceDraft::getByAutomaticRoom($automaticBingoId);

        if (!$draft) {
            return;
        }

        $currencies = $draft->currencies ?? [];
        $prices = $draft->card_prices ?? [];

        foreach ($currencies as $index => $currency) {
            BingoCardPrice::updateOrCreate(
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
