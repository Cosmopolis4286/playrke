<?php

namespace App\Services\ExchangeRate;

use App\DTOs\ExchangeRate\ExchangeRateDTO;
use App\Http\Resources\ExchangeRate\ExchangeRateResource;
use App\Services\Roles\RoleUserService;
use DomainException;

/**
 * Servicio de aplicación para exponer
 * los casos de uso del dominio ExchangeRate.
 *
 * Responsabilidades:
 * - Resolver contexto de usuario
 * - Delegar operaciones a servicios especializados
 * - Mantener controllers desacoplados
 *
 * NO contiene lógica de dominio ni infraestructura.
 */
final class ExchangeRateService
{
    public function __construct(
        private RoleUserService $roleUserService,
        private ExchangeRateContextService $context,
        private ExchangeRateCrudService $crud,
        private ExchangeRateUpdaterService $updater,
    ) {}

    /**
     * Obtener contexto para visualización de tasas.
     *
     * @param string|null $publicToken Token público de API
     * @return array
     */
    public function context(?string $publicToken = null): array
    {
        return $this->context->resolve($publicToken)->toView();
    }

    /**
     * Obtener todas las tasas de cambio filtradas según contexto y rol.
     *
     * @param string|null $publicToken Token público opcional
     * @return array
     */
    public function getAll(?string $publicToken = null): array
    {
        return $this->context->resolve($publicToken)->exchangeRates->toArray();
    }

    /**
     * Crear una tasa de cambio.
     *
     * @param ExchangeRateDTO $dto
     * @return bool
     *
     * @throws DomainException
     */
    public function create(ExchangeRateDTO $dto): bool
    {
        return $this->crud->create(
            $dto,
            $this->resolveUserId($dto->tokenId)
        );
    }

    /**
     * Obtener una tasa de cambio para visualización.
     *
     * @param string $exchangeRateId
     * @return ExchangeRateResource
     *
     * @throws DomainException
     */
    public function show(string $exchangeRateId): ExchangeRateResource
    {
        return $this->crud->show(
            $exchangeRateId,
            $this->resolveUserId()
        );
    }

    /**
     * Actualizar una tasa de cambio.
     *
     * @param ExchangeRateDTO $dto
     * @return bool
     *
     * @throws DomainException
     */
    public function update(ExchangeRateDTO $dto): bool
    {
        return $this->crud->update(
            $dto,
            $this->resolveUserId($dto->tokenId)
        );
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) de una tasa de cambio.
     *
     * @param string $exchangeRateId
     * @return bool
     *
     * @throws DomainException
     */
    public function toggleStatus(string $exchangeRateId): bool
    {
        return $this->crud->changeStatusByRole(
            $exchangeRateId,
            $this->resolveUserId()
        );
    }

    /**
     * Eliminar una tasa de cambio (soft delete).
     *
     * @param string $exchangeRateId
     * @return bool
     *
     * @throws DomainException
     */
    public function delete(string $exchangeRateId): bool
    {
        return $this->crud->deleteByRole(
            $exchangeRateId,
            $this->resolveUserId()
        );
    }

    /**
     * Actualizar tasas desde proveedor externo.
     *
     * @param string|null $publicToken
     * @return bool
     *
     * @throws DomainException
     */
    public function updateRates(?string $publicToken = null): bool
    {
        return $this->updater->updateRates(
            $this->resolveUserId($publicToken)
        );
    }

    /**
     * Obtener contexto histórico de tasas.
     *
     * @return array
     */
    public function historicalContext(): array
    {
        return [];
    }

    /* =====================================================
     | Helpers internos
     |=====================================================*/

    /**
     * Resuelve el ID del usuario autenticado o desde un token público.
     *
     * @param string|null $publicToken
     * @return int
     *
     * @throws DomainException
     */
    private function resolveUserId(?string $publicToken = null): int
    {
        $user = $this->roleUserService->resolveUser($publicToken);

        if (empty($user['id'])) {
            throw new DomainException('No se pudo resolver el usuario');
        }

        return (int) $user['id'];
    }
}
