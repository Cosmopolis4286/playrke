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

    /**
     * Obtiene el contexto necesario para la visualización de tasas de cambio.
     *
     * Este método resuelve la información según el usuario que realiza la petición,
     * diferenciando entre usuario autenticado y usuario público.
     *
     * Para usuarios autenticados, incluye layout específico, monedas disponibles
     * para el comercio asociado y listado de tasas de cambio.
     * Para usuarios públicos, muestra un layout de invitado y oculta las monedas.
     *
     * @param \Illuminate\Http\Request $request Instancia de la petición HTTP.
     *
     * @return \App\DTOs\ExchangeRate\ExchangeRateContextDTO Contexto listo para la vista.
     *
     * @throws \DomainException Si no se encuentra el comercio asociado al usuario.
     */
    public function resolve(Request $request): ExchangeRateContextDTO
    {
        [$user, $isLogin] = $this->resolveUser($request);

        $commerce = $user->commerce
            ?? throw new DomainException('Comercio no encontrado');

        if ($isLogin) {
            // Opción para actualizar tasas automáticamente para usuarios autenticados.
            // app(ExchangeRateUpdater::class)->updateRates($user->id, true);
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
                ? $this->resolveAvailableCurrencies($user->id, $commerce->id)
                : [],
            exchangeRates: $this->commerceExchangeRate->getAvailableExchangeRates($commerce->id)
        );
    }

    /**
     * Resolver usuario autenticado o público
     */
    private function resolveUser(Request $request): array
    {
        if (Auth::check()) {
            return [Auth::user(), true];
        }

        $user = $request->attributes->get('public_user');
        if (!$user) {
            abort(404);
        }

        return [$user, false];
    }

    private function resolveAvailableCurrencies(int $userId, string $creatorId): array
    {
        // Obtener monedas disponibles para el usuario y filtrar el id 1
        $currencyIds = collect($this->currency->getAvailableCurrencies($userId))
            ->filter(fn($id) => $id !== 1)
            ->values()
            ->all();

        if (empty($currencyIds)) {
            return [];
        }

        return [];
    }
}
