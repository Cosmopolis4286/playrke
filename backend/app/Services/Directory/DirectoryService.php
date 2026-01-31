<?php

namespace App\Services\Directory;

use App\DTOs\Directory\ExchangeRateDTO;
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
final class DirectoryService
{
    public function __construct(
        private readonly RoleUserService $roleUserService,
        private readonly DirectoryContextService $context,
        // private readonly ExchangeRateCrudService $crud,
    ) {}

    /**
     * Obtener contexto para el directorio de acuerdo al codigo ISO.
     *
     * @param string|null $countryCode Codigo ISO del país (opcional)
     * @return array Contexto listo para la vista
     */
    public function context(): array
    {
        return $this->context->resolve($countryCode)->toView();
    }

    // /**
    //  * Obtener todas las tasas de cambio filtradas según contexto y rol.
    //  *
    //  * @param string|null $publicToken Token público opcional
    //  * @return array Listado de tasas de cambio
    //  */
    // public function getAll(?string $publicToken = null): array
    // {
    //     return $this->context->getAll($publicToken);
    // }

    // /**
    //  * Crear una tasa de cambio.
    //  *
    //  * @param ExchangeRateDTO $dto DTO con los datos de la tasa de cambio
    //  * @return bool True si la creación fue exitosa
    //  *
    //  * @throws DomainException Si no se puede resolver el usuario o falla la creación
    //  */
    // public function create(ExchangeRateDTO $dto): bool
    // {
    //     $userId = $this->resolveUserId($dto->tokenId);
    //     return $this->crud->create($dto, $userId);
    // }

    // /**
    //  * Obtener una tasa de cambio para visualización.
    //  *
    //  * @param string $exchangeRateId ID de la tasa de cambio
    //  * @return ExchangeRateResource Recurso para visualización
    //  *
    //  * @throws DomainException Si no se puede resolver el usuario o no existe la tasa
    //  */
    // public function show(string $exchangeRateId): ExchangeRateResource
    // {
    //     $userId = $this->resolveUserId();
    //     return $this->crud->show($exchangeRateId, $userId);
    // }

    // /**
    //  * Actualizar una tasa de cambio.
    //  *
    //  * @param ExchangeRateDTO $dto DTO con datos actualizados
    //  * @return bool True si la actualización fue exitosa
    //  *
    //  * @throws DomainException Si no se puede resolver el usuario o falla la actualización
    //  */
    // public function update(ExchangeRateDTO $dto): bool
    // {
    //     $userId = $this->resolveUserId($dto->tokenId);
    //     return $this->crud->update($dto, $userId);
    // }

    // /**
    //  * Alternar estado (ACTIVE / INACTIVE) de una tasa de cambio.
    //  *
    //  * @param string $exchangeRateId ID de la tasa de cambio
    //  * @return bool True si el cambio fue exitoso
    //  *
    //  * @throws DomainException Si no se puede resolver el usuario o falla el cambio
    //  */
    // public function toggleStatus(string $exchangeRateId): bool
    // {
    //     $userId = $this->resolveUserId();
    //     return $this->crud->changeStatusByRole($exchangeRateId, $userId);
    // }

    // /**
    //  * Eliminar una tasa de cambio (soft delete).
    //  *
    //  * @param string $exchangeRateId ID de la tasa de cambio
    //  * @return bool True si la eliminación fue exitosa
    //  *
    //  * @throws DomainException Si no se puede resolver el usuario o falla la eliminación
    //  */
    // public function delete(string $exchangeRateId): bool
    // {
    //     $userId = $this->resolveUserId();
    //     return $this->crud->deleteByRole($exchangeRateId, $userId);
    // }
}
