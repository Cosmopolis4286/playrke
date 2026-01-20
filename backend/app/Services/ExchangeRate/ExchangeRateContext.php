<?php

namespace App\Services\ExchangeRate;

use App\Contracts\Roles\Commerce\CommerceExchangeRateRepositoryInterface;
use App\DTOs\ExchangeRate\ExchangeRateContextDTO;
use App\Services\Currencies\CurrencyService;
use App\Services\Roles\RoleUserService;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class ExchangeRateContext
{
    public function __construct(
        protected CommerceExchangeRateRepositoryInterface $commerceExchangeRate,
        protected CurrencyService $currency,
        protected RoleUserService $roleUserService
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
        [$userId, $isLogged] = $this->roleUserService->resolveUser($request);

        if ($isLogged) {
            // Opción para actualizar tasas automáticamente para usuarios autenticados.
            // app(ExchangeRateUpdater::class)->updateRates($userId);
        }

        $userRolePrefix = $this->roleUserService->getActiveUserRolePrefix($userId) ?? '';

        return new ExchangeRateContextDTO(
            currencies: $this->resolveAvailableCurrencies($userId),
            exchangeRates: $this->resolveAvailableExchangeRates($userRolePrefix, $userId),
            roleUser: $userRolePrefix,
            tokenId: !$isLogged ? $userId : ''
        );
    }

    private function resolveAvailableCurrencies(int $userId): array|object
    {
        // Obtener monedas disponibles para el usuario
        return $this->currency->getAvailableCurrencies($userId, true);
    }

    private function resolveAvailableExchangeRates(string $userRolePrefix, int $userId): Collection
    {
        switch ($userRolePrefix) {
            case 'admin': // Administrador
                // TODO: Definir lógica para admin si aplica
                return  collect();
            case 'commerce': // Comercio
                return $this->handleCommerceExchangeRates($userId);
            case 'customer': // Cliente
                return collect();
            case 'player': // Jugador
                return $this->handlePlayerExchangeRates($userId);
            case 'seller': // Vendedor
                return $this->handleSellerExchangeRates($userId);
            default:
                return  collect();
        }
    }

    private function handlePlayerExchangeRates(int $userId): Collection
    {
        return $this->commerceExchangeRate->getAvailableExchangeRates($userId);
    }

    private function handleSellerExchangeRates(int $sellerId): Collection
    {
        return collect();
    }

    private function handleCommerceExchangeRates(int $commerceId): Collection
    {
        return $this->commerceExchangeRate->getAvailableExchangeRates($commerceId);
    }
}
