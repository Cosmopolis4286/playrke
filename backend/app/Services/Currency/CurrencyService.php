<?php

namespace App\Services\Currency;

use App\Contracts\CurrencyRepositoryInterface;
use App\Contracts\Roles\Commerce\CommerceRepositoryInterface;
use App\Contracts\Roles\Seller\SellerRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Services\Roles\Commerce\CommerceCurrencyService;
use App\Services\Roles\RoleUserService;
use Illuminate\Database\Eloquent\Collection;

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
     * @return array|Collection Listado de IDs o códigos de moneda disponibles.
     */
    public function getAvailableCurrencies(int $userId, bool $withCurrency = false,  bool $withCountry = false): array|Collection
    {
        $userRolePrefix = $this->roleUserService->getActiveUserRolePrefix($userId);

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
            return $this->currencyRepository->getByIdsWithRelations(['country'], $currenciesIds);
        } else if ($withCurrency) {
            return $this->currencyRepository->getByIds($currenciesIds);
        }

        return $currenciesIds;
    }

    /**
     * Obtiene las monedas disponibles con su level del pivot incluido.
     * Soporta los roles 'commerce' y 'seller'.
     * Siempre retorna Eloquent\Collection (vacía si no aplica).
     *
     * @param int $userId ID del usuario.
     * @return Collection
     */
    public function getAvailableCurrenciesWithLevel(int $userId): Collection
    {
        $userRolePrefix = $this->roleUserService->getActiveUserRolePrefix($userId);

        $levelMap = match ($userRolePrefix) {
            'commerce' => $this->getLevelMapForCommerce($userId),
            'seller'   => $this->getLevelMapForSeller($userId),
            default    => [],
        };

        if (empty($levelMap)) {
            return new Collection();
        }

        $currencies = $this->currencyRepository->getByIdsWithRelations(
            ['country'],
            array_keys($levelMap)
        );

        $currencies->each(function ($currency) use ($levelMap) {
            $currency->level = $levelMap[$currency->id] ?? 99;
        });

        return $currencies;
    }

    /**
     * Retorna el mapa currency_id => level para un comercio.
     *
     * @param int $userId
     * @return array<string, int>
     */
    private function getLevelMapForCommerce(int $userId): array
    {
        $commerceId = $this->commerceRepository->getValueByUserId($userId, 'id');

        if (!$commerceId) {
            return [];
        }

        return $this->commerceCurrencyService->getAvailableCurrenciesWithLevel($commerceId);
    }

    /**
     * Retorna el mapa currency_id => level para un seller
     * usando la relación sellers_currencies (status ACTIVE, MIN level).
     *
     * @param int $userId
     * @return array<string, int>
     */
    private function getLevelMapForSeller(int $userId): array
    {
        $sellerId = $this->sellerRepository->getByUserId((string) $userId)?->id;

        if (!$sellerId) {
            return [];
        }

        return \Illuminate\Support\Facades\DB::table('sellers_currencies')
            ->select('currency_id', \Illuminate\Support\Facades\DB::raw('MIN(level) as level'))
            ->where('seller_id', $sellerId)
            ->where('status', 'ACTIVE')
            ->groupBy('currency_id')
            ->get()
            ->pluck('level', 'currency_id')
            ->map(fn($level) => (int) $level)
            ->all();
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
