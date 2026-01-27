<?php

namespace App\Services\ExchangeRate;

use App\Contracts\Roles\Commerce\CommerceExchangeRateRepositoryInterface;
use App\DTOs\ExchangeRate\ExchangeRateContextDTO;
use App\Services\Currency\CurrencyService;
use App\Services\Roles\RoleUserService;
use App\Traits\HashedTableDataTrait;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

class ExchangeRateContextService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly CommerceExchangeRateRepositoryInterface $commerceExchangeRate,
        private readonly CurrencyService $currency,
        private readonly RoleUserService $roleUserService
    ) {}

    /**
     * Resuelve el contexto para la visualización de tasas de cambio.
     *
     * Diferencia entre usuario autenticado y público.
     * Usuarios autenticados reciben monedas y tasas según su rol.
     * Usuarios públicos obtienen tokenId para acceso limitado.
     *
     * @param string|null $tokenId Token público opcional para identificar usuario.
     * @return ExchangeRateContextDTO Contexto listo para la vista.
     *
     * @throws DomainException Si no se puede resolver el rol activo.
     */
    public function resolve(?string $tokenId = null): ExchangeRateContextDTO
    {
        ['id' => $userId, 'authenticated' => $isLogged] = $this->roleUserService->resolveUser($tokenId);

        $userRolePrefix = $this->roleUserService->getActiveUserRolePrefix($userId);

        return new ExchangeRateContextDTO(
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

        $context = match ($role) {
            'commerce' => $this->commerceExchangeRate->getAvailableExchangeRates($entityId),
            'customer', 'admin' => collect(),
            default => collect(),
        };

        return $context;

        // Define columnas visibles y keys fuente para la tabla
        $columns = ['Nombre', 'País', 'Teléfono', 'Email', 'Status'];
        $rowKeys = ['name', 'Country.name', 'phone', 'User.email', 'status_label'];

        // Aplica transformación de contexto para tablas Inertia
        $context = $this->processContextTableData($context->toArray(), 'exchangeRates', $columns, $rowKeys);
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
