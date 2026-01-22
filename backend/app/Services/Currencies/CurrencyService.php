<?php

namespace App\Services\Currencies;

use App\Contracts\CurrencyRepositoryInterface;
use App\Contracts\Roles\Commerce\CommerceRepositoryInterface;
use App\Contracts\Roles\Seller\SellerRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Services\Roles\Commerce\CommerceCurrencyService;
use App\Services\Roles\RoleUserService;

class CurrencyService
{
    public function __construct(
        protected CurrencyRepositoryInterface $currencyRepository,
        protected UserRepositoryInterface $userRepository,
        protected CommerceRepositoryInterface $commerceRepository,
        protected SellerRepositoryInterface $sellerRepository,
        protected CommerceCurrencyService $commerceCurrencyService,
        protected RoleUserService $roleUserService
    ) {}

    /**
     * Obtiene las monedas disponibles según el tipo de usuario.
     *
     * @param int $userId ID del usuario.
     * @param bool $withCurrency Obtener informacion de las monedas.
     * @param bool $withCountry Obtener informacion del pais de las monedas.
     * @return array Listado de IDs o códigos de moneda disponibles.
     */
    public function getAvailableCurrencies(int $userId, ?bool $withCurrency,  ?bool $withCountry): array|object
    {
        $userRolePrefix = $this->roleUserService->getActiveUserRolePrefix($userId);

        if (!$userRolePrefix) {
            return [];
        }

        $currenciesIds = [];

        switch ($userRolePrefix) {
            case 'player': // Jugador
                $currenciesIds = $this->handlePlayerCurrencies($userId);
                break;
            case 'admin':
                // TODO: Definir lógica para admin si aplica
                return [];
                break;
            case 'commerce': // Comercio
                $currenciesIds = $this->handleCommerceCurrencies($userId);
                break;
            case 'seller':
            case 'customer': // Cliente
                $currenciesIds = $this->handleSellerCurrencies($userId);
                break;
            default:
                return [];
        }

        if ($withCurrency && $withCountry) {
            return $this->currencyRepository->getAllWithRelations(
                ['country:id,name', 'user:id,email'],
                ['id', 'country_id', 'user_id', 'name', 'phone', 'status']
            );
        } else if ($withCurrency) {
            return $this->currencyRepository->getByIds($currenciesIds);
        }

        return $currenciesIds;
    }

    /**
     * Obtiene las monedas disponibles para un jugador.
     *
     * @param int $userId
     * @return array
     */
    protected function handlePlayerCurrencies(int $userId): array
    {
        $countryId = $this->userRepository->getValueById($userId, 'country');

        return $countryId ? [$countryId] : [];
    }

    /**
     * Obtiene las monedas disponibles para un comercio.
     *
     * @param int $userId
     * @return array
     */
    protected function handleCommerceCurrencies(int $userId): array
    {
        $commerceId = $this->commerceRepository->getValueByUserId($userId, 'id');

        if (!$commerceId) {
            return [];
        }

        return $this->commerceCurrencyService->getAvailableCurrencies($commerceId);
    }

    /**
     * Obtiene las monedas disponibles para un vendedor o cliente.
     *
     * @param int $userId
     * @return array
     */
    protected function handleSellerCurrencies(int $userId): array
    {
        $sellerId = $this->sellerRepository->getValueByUserId($userId, 'id');

        if (!$sellerId) {
            return [];
        }

        return [];

        // return $this->sellerCurrencyService->getAvailableCurrencies($sellerId);
    }
}
