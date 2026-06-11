<?php

namespace App\Http\Controllers\Platform;

use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Endpoints públicos del feed de ganadores en vivo.
 */
class LiveWinsController extends Controller
{
    /**
     * Últimos ganadores registrados, usados para sembrar el ticker
     * antes de que lleguen eventos en tiempo real.
     */
    public function recent(): JsonResponse
    {
        $prizes = BingoCardPrize::query()
            ->with(['creatorUser:id,name', 'currency:id,iso_code,symbol,decimal_places'])
            ->latest('created_at')
            ->limit(10)
            ->get();

        $wins = $prizes->map(function (BingoCardPrize $prize) {
            $currency = $prize->currency;
            $isoCode  = $currency?->iso_code ?? 'USD';

            return [
                'id'             => (string) $prize->id,
                'game'           => 'Bingo',
                'game_slug'      => 'bingo',
                'winner_name'    => $prize->creatorUser?->name ?? 'Jugador',
                'amount'         => (float) $prize->usd_amount,
                'currency'       => $isoCode,
                'currency_symbol'=> $currency?->symbol ?? '$',
                'is_usd'         => $isoCode === 'USD',
                'local_amount'   => (float) $prize->local_amount,
                'decimal_places' => $currency?->decimal_places ?? 2,
                'timestamp'      => $prize->created_at?->toISOString(),
            ];
        });

        return response()->json(['wins' => $wins]);
    }
}
