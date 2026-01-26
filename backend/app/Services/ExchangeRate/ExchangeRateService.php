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
        private readonly RoleUserService $roleUserService,
        private readonly ExchangeRateContextService $context,
        private readonly ExchangeRateCrudService $crud,
        private readonly ExchangeRateUpdaterService $updater,
        private ExchangeRateApiService $api,
    ) {}

    /**
     * Obtener contexto para visualización de tasas.
     *
     * @param string|null $publicToken Token público de API
     * @return array Contexto listo para la vista
     */
    public function context(?string $publicToken = null): array
    {
        return $this->context->resolve($publicToken)->toView();
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
        $userId = $this->resolveUserId($dto->tokenId);
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
    public function show(string $exchangeRateId): ExchangeRateResource
    {
        $userId = $this->resolveUserId();
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
        $userId = $this->resolveUserId($dto->tokenId);
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
        $userId = $this->resolveUserId();
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
        $userId = $this->resolveUserId();
        return $this->crud->deleteByRole($exchangeRateId, $userId);
    }

    /**
     * Retorna las tasas crudas obtenidas de la API externa para mostrar en confirmación.
     *
     * @param string|null $publicToken Token público opcional para resolver usuario
     * @return array|null Retorna un arreglo con las tasas si la llamada fue exitosa,
     *                    o null si ocurrió algún error.
     */
    public function getRawRates(?string $publicToken = null): ?array
    {
        $userId = $this->resolveUserId($publicToken);
        return $this->api->fetch();
    }

    /**
     * Actualizar tasas desde proveedor externo.
     *
     * @param string|null $publicToken Token público opcional para resolver usuario
     * @return bool True si la actualización fue exitosa
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la actualización
     */
    public function updateRates(?string $publicToken = null): bool
    {
        $userId = $this->resolveUserId($publicToken);
        return $this->updater->updateRates($userId);
    }

    /**
     * Obtener contexto histórico de tasas.
     *
     * @return array Contexto histórico (vacío actualmente)
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
     * @param string|null $publicToken Token público opcional
     * @return int ID del usuario
     *
     * @throws DomainException Si no se pudo resolver el usuario
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
