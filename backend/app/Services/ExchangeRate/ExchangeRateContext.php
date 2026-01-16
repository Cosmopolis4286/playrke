<?php

namespace App\Services\ExchangeRate;

use App\Contracts\Roles\Commerce\CommerceExchangeRateRepositoryInterface;
use App\DTOs\ExchangeRate\ExchangeRateContextDTO;
use App\Models\Currency;
use App\Services\Currencies\CurrencyService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExchangeRateContext
{
    public function __construct(
        protected CommerceExchangeRateRepositoryInterface $commerceExchangeRate,
        protected CurrencyService $currency
    ) {}

    public function resolve(Request $request): ExchangeRateContextDTO
    {
        [$userId, $isLogin] = $this->resolveUser($request);

        $commerce = $user->commerce
            ?? throw new DomainException('Comercio no encontrado');

        if ($isLogin) {
            // app(ExchangeRateUpdater::class)
            //     ->updateRates($user->id, true);
        }

        return new ExchangeRateContextDTO(
            bladeLayout: $isLogin ? 'layouts.layout-auth' : 'layouts.layout-guest',
            title: 'Tasas de Cambio',
            name: 'exchange-rate',
            returnBtn: true,
            creator: [
                'type' => 'commerce',
                'id' => encrypt($commerce->id),
            ],
            currencies: $isLogin
                ? $this->resolveAvailableCurrencies($userId, $commerce->id)
                : [],
            exchangeRates: $this->commerceExchangeRate
                ->getAvailableExchangeRates($commerce->id)
        );
    }


    /**
     * Resolver usuario autenticado o público
     */
    private function resolveUser(Request $request): array
    {
        if (Auth::check()) {
            return [Auth::id(), true];
        }

        $user = $request->attributes->get('public_user');
        if (!$user) {
            abort(404);
        }

        return [$user, false];
    }

    /**
     * Resolver monedas disponibles para el comercio
     */
    private function resolveAvailableCurrencies(int $userId, string $creatorId): array
    {
        $currencyIds = array_filter(
            $this->currency->getAvailableCurrencies($userId),
            fn($id) => $id !== 1
        );

        return Currency::query()
            ->whereIn('id', $currencyIds)
            ->whereDoesntHave('commerceExchangeRate', function ($query) use ($creatorId) {
                $query->where('commerce_id', $creatorId);
            })
            ->get(['id', 'symbol', 'description'])
            ->toArray();
    }
}
