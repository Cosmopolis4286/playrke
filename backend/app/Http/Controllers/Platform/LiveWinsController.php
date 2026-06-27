<?php

namespace App\Http\Controllers\Platform;

use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Games\Trivia\TriviaParticipant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Endpoints públicos del feed de ganadores en vivo.
 */
class LiveWinsController extends Controller
{
    private const WINDOW_MINUTES = 5;
    private const LIMIT = 10;

    /**
     * Últimos ganadores registrados (Bingo + Trivia), usados para sembrar
     * el ticker antes de que lleguen eventos en tiempo real.
     */
    public function recent(): JsonResponse
    {
        $wins = $this->recentBingoWins()
            ->concat($this->recentTriviaWins())
            ->sortByDesc('timestamp')
            ->take(self::LIMIT)
            ->values();

        return response()->json(['wins' => $wins]);
    }

    private function recentBingoWins(): Collection
    {
        $prizes = BingoCardPrize::query()
            ->with(['creatorUser:id,first_name,last_name,username', 'currency:id,iso_code,symbol,decimal_places'])
            ->where('created_at', '>=', now()->subMinutes(self::WINDOW_MINUTES))
            ->latest('created_at')
            ->limit(self::LIMIT)
            ->get();

        return $prizes->map(function (BingoCardPrize $prize) {
            $currency = $prize->currency;
            $isoCode  = $currency?->iso_code ?? 'USD';

            return [
                'id'             => 'bingo-' . $prize->id,
                'game'           => 'Bingo',
                'game_slug'      => 'bingo',
                'winner_name'    => $prize->creatorUser?->full_name ?: ($prize->creatorUser?->username ?? 'Jugador'),
                'amount'         => (float) $prize->usd_amount,
                'currency'       => $isoCode,
                'currency_symbol'=> $currency?->symbol ?? '$',
                'is_usd'         => $isoCode === 'USD',
                'local_amount'   => (float) $prize->local_amount,
                'decimal_places' => $currency?->decimal_places ?? 2,
                'timestamp'      => $prize->created_at?->toISOString(),
            ];
        });
    }

    /**
     * Ganadores en 1° posición de Trivia. El usuario y la moneda viven en MySQL
     * mientras que trivia_participants está en pgsql_trivia, así que se resuelven
     * manualmente en lote en vez de relaciones Eloquent cross-database.
     */
    private function recentTriviaWins(): Collection
    {
        $participants = TriviaParticipant::query()
            ->where('status', 'winner')
            ->where('final_rank', 1)
            ->where('updated_at', '>=', now()->subMinutes(self::WINDOW_MINUTES))
            ->latest('updated_at')
            ->limit(self::LIMIT)
            ->get(['room_id', 'user_id', 'prize_won', 'paid_currency_id', 'updated_at']);

        if ($participants->isEmpty()) {
            return collect();
        }

        $userIds     = $participants->pluck('user_id')->unique()->all();
        $roomIds     = $participants->pluck('room_id')->unique()->all();
        $currencyIds = $participants->pluck('paid_currency_id')->filter()->unique()->all();

        $users      = User::whereIn('id', $userIds)->get(['id', 'first_name', 'last_name', 'username'])->keyBy('id');
        $currencies = Currency::whereIn('id', $currencyIds)->get(['id', 'iso_code', 'symbol', 'decimal_places'])->keyBy('id');
        $rooms      = TriviaRoom::whereIn('id', $roomIds)->get(['id', 'prize_currency_id'])->keyBy('id');

        return $participants->map(function (TriviaParticipant $p) use ($users, $currencies, $rooms) {
            $user        = $users->get($p->user_id);
            $currencyId  = $p->paid_currency_id ?? $rooms->get($p->room_id)?->prize_currency_id;
            $currency    = $currencyId ? $currencies->get($currencyId) : null;
            $isoCode     = $currency?->iso_code ?? 'USD';
            $localAmount = (float) $p->prize_won;
            $usdAmount   = $currencyId ? $this->convertToUsd($localAmount, (string) $currencyId) : $localAmount;

            return [
                'id'             => 'trivia-' . $p->room_id . '-' . $p->user_id,
                'game'           => 'Trivia',
                'game_slug'      => 'trivia',
                'winner_name'    => $user?->full_name ?: ($user?->username ?? 'Jugador'),
                'amount'         => $usdAmount,
                'currency'       => $isoCode,
                'currency_symbol'=> $currency?->symbol ?? '$',
                'is_usd'         => $isoCode === 'USD',
                'local_amount'   => $localAmount,
                'decimal_places' => $currency?->decimal_places ?? 2,
                'timestamp'      => $p->updated_at?->toISOString(),
            ];
        });
    }

    /**
     * Convierte un monto en moneda local a USD usando la tasa de cambio activa
     * de esa moneda — mismo patrón que BingoDrawService/PrizeDistributionService.
     */
    private function convertToUsd(float $localAmount, string $currencyId): float
    {
        $rate = (float) ExchangeRate::where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->orderByDesc('created_at')
            ->value('amount');

        if ($rate <= 0) {
            return $localAmount;
        }

        return round($localAmount / $rate, 4);
    }
}
