<?php

namespace App\Services\Directory;

use App\Contracts\Roles\Commerce\CommerceExchangeRateRepositoryInterface;
use App\DTOs\Directory\DirectoryContextDTO;
use App\Services\Currency\CurrencyService;
use App\Services\Roles\RoleUserService;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

class DirectoryContextService
{
    public function __construct(
        private readonly CommerceExchangeRateRepositoryInterface $commerceExchangeRate,
        private readonly CurrencyService $currency,
        private readonly RoleUserService $roleUserService
    ) {}

    /**
     * Resuelve el contexto del directorio de acuerdo al codigo ISO.
     *
     * @param string|null $countryCode Codigo ISO del país (opcional).
     * @return DirectoryContextDTO Contexto listo para la vista.
     *
     * @throws DomainException Si no se puede resolver el rol activo.
     */
    public function resolve(?string $countryCode = null): DirectoryContextDTO
    {
        ['id' => $userId, 'authenticated' => $isLogged] = $this->roleUserService->resolveUser();

        $userRolePrefix = $this->roleUserService->getActiveUserRolePrefix($userId);

        return new DirectoryContextDTO(
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
        // Definir ID de la entidad para búsqueda basada en el rol
        $entityId = $this->roleUserService->getUserRoleEntityId($userId, $role);

        return match ($role) {
            'commerce' => $this->commerceExchangeRate->getAvailableExchangeRates($entityId),
            'customer', 'admin' => collect(),
            default => collect(),
        };
    }

    /**
     * Obtener todas las tasas de cambio filtradas según contexto y rol.
     *
     * @param string|null $publicToken Token público opcional
     * @return array Listado de tasas de cambio
     */
    public function getAll(?string $publicToken = null): array
    {
        ['id' => $userId, 'authenticated' => $isLogged] = $this->roleUserService->resolveUser($publicToken);
        $userRolePrefix = $isLogged ? $this->roleUserService->getActiveUserRolePrefix($userId) ?? '' : '';

        return $this->resolveExchangeRatesByRole($userRolePrefix, $userId)->toArray();
    }
}
