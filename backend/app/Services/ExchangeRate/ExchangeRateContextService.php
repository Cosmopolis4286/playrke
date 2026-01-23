<?php

namespace App\Services\ExchangeRate;

use App\Contracts\Roles\Commerce\CommerceExchangeRateRepositoryInterface;
use App\DTOs\ExchangeRate\ExchangeRateContextServiceDTO;
use App\Services\Currencies\CurrencyService;
use App\Services\Roles\RoleUserService;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

class ExchangeRateContextService
{
    public function __construct(
        protected CommerceExchangeRateRepositoryInterface $commerceExchangeRate,
        protected CurrencyService $currency,
        protected RoleUserService $roleUserService
    ) {}

    /**
     * Resuelve el contexto para la visualización de tasas de cambio.
     *
     * Diferencia entre usuario autenticado y público.
     * Usuarios autenticados reciben monedas y tasas según su rol.
     * Usuarios públicos obtienen tokenId para acceso limitado.
     *
     * @param string|null $token Token público opcional para identificar usuario.
     * @return ExchangeRateContextServiceDTO Contexto listo para la vista.
     *
     * @throws DomainException Si no se puede resolver el rol activo.
     */
    public function resolve(?string $token = null): ExchangeRateContextServiceDTO
    {
        ['id' => $userId, 'authenticated' => $isLogged] = $this->roleUserService->resolveUser($token);

        $userRolePrefix = $isLogged
            ? $this->roleUserService->getActiveUserRolePrefix($userId) ?? ''
            : '';

        return new ExchangeRateContextServiceDTO(
            currencies: $isLogged ? $this->currency->getAvailableCurrencies($userId, true, true) : collect(),
            exchangeRates: $this->resolveExchangeRatesByRole($userRolePrefix, $userId),
            roleUser: $userRolePrefix,
            tokenId: $isLogged ? '' : $userId
        );
    }

    /**
     * Resuelve las tasas de cambio según el prefijo de rol activo.
     *
     * @param string $role Prefijo de rol activo (ej. 'commerce', 'player').
     * @param int $userId ID del usuario o entidad relacionada.
     *
     * @return Collection Lista de tasas de cambio disponibles.
     */
    protected function resolveExchangeRatesByRole(string $role, int $userId): Collection
    {
        // Definir ID de la entidad de busqueda
        $entityId = $this->roleUserService->getUserRoleEntityId($userId, $role);

        return match ($role) {
            'commerce' => $this->commerceExchangeRate->getAvailableExchangeRates($entityId),
            'customer', 'admin' => collect(),
            default => collect(),
        };
    }
}
