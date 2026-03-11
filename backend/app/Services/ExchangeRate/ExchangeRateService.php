<?php

namespace App\Services\ExchangeRate;

use App\Contracts\Roles\Commerce\CommerceCurrencyRepositoryInterface;
use App\DTOs\ExchangeRate\ExchangeRateDTO;
use App\Http\Resources\ExchangeRate\ExchangeRateResource;
use App\Http\Resources\ExchangeRate\AdminExchangeRateResource;
use App\Services\Roles\RoleEntityResolver;
use App\Services\Roles\RoleUserService;
use App\Services\Security\UserContextService;
use DomainException;

/**
 * Servicio de aplicación para exponer
 * los casos de uso del dominio ExchangeRate.
 *
 * Responsabilidades:
 * - Resolver contexto de usuario (auth / token)
 * - Resolver rol y entidad asociada
 * - Delegar operaciones a servicios especializados
 * - Mantener controllers desacoplados
 *
 * NO contiene lógica de dominio ni infraestructura HTTP.
 */
final class ExchangeRateService
{
    public function __construct(
        private readonly UserContextService $userContext,
        private readonly RoleUserService $roleUserService,
        private readonly RoleEntityResolver $roleEntityResolver,
        private readonly ExchangeRateContextService $context,
        private readonly ExchangeRateCrudService $crud,
        private readonly ExchangeRateUpdaterService $updater,
        private readonly ExchangeRateApiService $api,
        private readonly CommerceCurrencyRepositoryInterface $commerceCurrencyRepository,
    ) {}

    /**
     * Obtener contexto para visualización de tasas.
     *
     * @param string|null $publicToken Token público de API
     * @return array Contexto listo para la vista
     */
    public function context(?string $publicToken = null): array
    {
        return $this->context
            ->resolve($publicToken)
            ->toView();
    }

    /**
     * Obtener todas las tasas de cambio filtradas según contexto y rol.
     *
     * @param string|null $publicToken Token público opcional
     * @return array Listado de tasas de cambio
     */
    public function getAll(?string $publicToken = null): array
    {
        return $this->context->getAll($publicToken);
    }

    /**
     * Crear una tasa de cambio.
     *
     * @param ExchangeRateDTO $dto DTO con los datos de la tasa de cambio
     * @return bool True si la creación fue exitosa
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la creación
     */
    public function create(ExchangeRateDTO $dto): bool
    {
        $userId = $this->userContext->resolveUserId($dto->tokenId);

        return $this->crud->create($dto, $userId);
    }

    /**
     * Obtener una tasa de cambio para visualización.
     *
     * @param string $exchangeRateId ID de la tasa de cambio
     * @return ExchangeRateResource Recurso para visualización
     *
     * @throws DomainException Si no se puede resolver el usuario o no existe la tasa
     */
    public function show(string $exchangeRateId): ExchangeRateResource|AdminExchangeRateResource
    {
        $userId = $this->userContext->resolveUserId();

        return $this->crud->show($exchangeRateId, $userId);
    }

    /**
     * Actualizar una tasa de cambio.
     *
     * @param ExchangeRateDTO $dto DTO con datos actualizados
     * @return bool True si la actualización fue exitosa
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la actualización
     */
    public function update(ExchangeRateDTO $dto): bool
    {
        $userId = $this->userContext->resolveUserId($dto->tokenId);

        return $this->crud->update($dto, $userId);
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) de una tasa de cambio.
     *
     * @param string $exchangeRateId ID de la tasa de cambio
     * @return bool True si el cambio fue exitoso
     *
     * @throws DomainException Si no se puede resolver el usuario o falla el cambio
     */
    public function toggleStatus(string $exchangeRateId): bool
    {
        $userId = $this->userContext->resolveUserId();

        return $this->crud->changeStatusByRole($exchangeRateId, $userId);
    }

    /**
     * Eliminar una tasa de cambio (soft delete).
     *
     * @param string $exchangeRateId ID de la tasa de cambio
     * @return bool True si la eliminación fue exitosa
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la eliminación
     */
    public function delete(string $exchangeRateId): bool
    {
        $userId = $this->userContext->resolveUserId();

        return $this->crud->deleteByRole($exchangeRateId, $userId);
    }

    /**
     * Retorna las tasas crudas obtenidas de la API externa para mostrar en confirmación.
     *
     * Solo retorna tasas de monedas que el comercio tiene activas en commercecurrencies.
     * Excluye el campo "Promedio" de la respuesta.
     *
     * @param string|null $publicToken Token público opcional para resolver usuario
     * @return array|null Retorna un arreglo con las tasas si la llamada fue exitosa,
     *                    o null si ocurrió algún error.
     */
    public function getRawRates(?string $publicToken = null): ?array
    {
        $userId = $this->userContext->resolveUserId($publicToken);

        $role = $this->roleUserService->getActiveUserRolePrefix($userId);

        $commerceId = $this->roleEntityResolver
            ->resolveEntityId($userId, $role);

        $rates = $this->api->fetch();

        if (empty($rates)) {
            return null;
        }

        $availableIsoCodes = $this->commerceCurrencyRepository
            ->getAvailableCurrenciesIsoCodes($commerceId);

        return collect($rates)
            ->filter(
                fn($value, $key) =>
                $key !== 'Promedio'
                    && $value != 0
                    && in_array($key, $availableIsoCodes, true)
            )
            ->toArray();
    }

    /**
     * Actualizar tasas con los valores confirmados por el usuario.
     *
     * @param array $rates Tasas confirmadas por el usuario (iso_code => value)
     * @param string|null $publicToken Token público opcional para resolver usuario
     * @return bool True si la actualización fue exitosa
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la actualización
     */
    public function updateRates(array $rates, ?string $publicToken = null): bool
    {
        $userId = $this->userContext->resolveUserId($publicToken);

        $role = $this->roleUserService->getActiveUserRolePrefix($userId);

        $commerceId = $this->roleEntityResolver
            ->resolveEntityId($userId, $role);

        $availableIsoCodes = $this->commerceCurrencyRepository
            ->getAvailableCurrenciesIsoCodes($commerceId);

        $filteredRates = collect($rates)
            ->filter(
                fn($value, $key) =>
                $key !== 'Promedio'
                    && $value != 0
                    && in_array($key, $availableIsoCodes, true)
            )
            ->toArray();

        return $this->updater->updateRates($userId, $filteredRates);
    }

    /**
     * Obtener contexto histórico de tasas de cambio por moneda.
     *
     * @param string $currencyId ID de la moneda
     * @param string|null $publicToken Token público opcional
     * @return array Contexto histórico para la vista
     */
    public function historicalContext(string $currencyId, ?string $publicToken = null): array
    {
        return $this->context->resolveHistorical($currencyId, $publicToken);
    }
}
