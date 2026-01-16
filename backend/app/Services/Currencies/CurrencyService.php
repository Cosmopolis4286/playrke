<?php

namespace App\Services\Currencies;

use App\Contracts\Roles\Commerce\CommerceRepositoryInterface;
use App\Contracts\Roles\RoleUserRepositoryInterface;
use App\Contracts\Roles\Seller\SellerRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Services\Roles\Commerce\CommerceCurrencyService;
use App\Services\Roles\Seller\SellerCurrencyService;

class CurrencyService
{
    public function __construct(
        protected RoleUserRepositoryInterface $roleUserRepository,
        protected UserRepositoryInterface $userRepository,
        protected CommerceRepositoryInterface $commerceRepository,
        protected SellerRepositoryInterface $sellerRepository,
        protected CommerceCurrencyService $commerceCurrencyService,
        // protected SellerCurrencyService $sellerCurrencyService,
    ) {}

    /**
     * Obtiene las monedas disponibles según el tipo de usuario.
     *
     * @param int $userId ID del usuario.
     * @return array Listado de IDs o códigos de moneda disponibles.
     */
    public function getAvailableCurrencies(int $userId): array
    {
        $userRolePrefix = $this->getActiveUserRolePrefix($userId);

        if (!$userRolePrefix) {
            return [];
        }

        switch ($userRolePrefix) {
            case 'player': // Jugador
                return $this->handlePlayerCurrencies($userId);

            case 'admin':
                // TODO: Definir lógica para admin si aplica
                return [];

            case 'commerce': // Comercio
                return $this->handleCommerceCurrencies($userId);

            case 'seller':
            case 'customer': // Cliente
                return $this->handleSellerCurrencies($userId);

            default:
                return [];
        }
    }

    /**
     * Resuelve el prefijo del tipo de usuario.
     *
     * @param int $userId
     * @return string|null
     */
    /**
     * Resuelve el prefijo del tipo de usuario basándose en roles activos.
     */
    protected function getActiveUserRolePrefix(int $userId): ?string
    {
        // Obtener roles activos asignados al usuario
        $activeRoles = $this->roleUserRepository->getActiveByUser($userId);

        foreach ($activeRoles as $roleUser) {
            $role = $roleUser->role;
            if ($role && $role->prefix) {
                // Retornamos el primer prefijo encontrado (ajustar lógica si hay más de uno)
                return strtolower($role->prefix);
            }
        }

        return null;
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
