<?php

namespace App\Services\Directory;

use App\DTOs\Directory\DirectoryDTO;
use App\Http\Resources\Directory\DirectoryResource;
use App\Services\Roles\RoleUserService;
use DomainException;

/**
 * Servicio de aplicación para exponer
 * los casos de uso del dominio Directory.
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
        private readonly DirectoryCrudService $crud,
    ) {}

    /**
     * Obtener contexto para el directorio de acuerdo al codigo ISO.
     *
     * @param string|null $countryCode Codigo ISO del país (opcional)
     * @return array Contexto listo para la vista
     */
    public function context(?string $countryCode = null): array
    {
        return $this->context->resolve($countryCode)->toView();
    }

    /**
     * Obtiene todos los registros de directorio del usuario autenticado.
     *
     * @return array Lista de registros
     */
    public function getAll(): array
    {
        $userId = $this->resolveUserId();
        return $this->crud->getAll($userId);
    }

    /**
     * Crea un nuevo registro de directorio.
     *
     * @param DirectoryDTO $dto DTO con los datos del registro
     * @return bool True si se creó correctamente
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la creación
     */
    public function create(DirectoryDTO $dto): bool
    {
        $userId = $this->resolveUserId();
        return $this->crud->create($dto, $userId);
    }

    /**
     * Obtiene un registro específico de directorio.
     *
     * @param string $id ID del registro
     * @return DirectoryResource Registro transformado
     *
     * @throws DomainException Si no se puede resolver el usuario o no existe el registro
     */
    public function show(string $id): DirectoryResource
    {
        $userId = $this->resolveUserId();
        return $this->crud->show($id, $userId);
    }

    /**
     * Actualiza un registro de directorio existente.
     *
     * @param DirectoryDTO $dto DTO con datos actualizados
     * @return bool True si se actualizó correctamente
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la actualización
     */
    public function update(DirectoryDTO $dto): bool
    {
        return $this->crud->update($dto);
    }

    /**
     * Elimina un registro de directorio (soft delete).
     *
     * @param string $id ID del registro
     * @return bool True si se eliminó correctamente
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la eliminación
     */
    public function delete(string $id): bool
    {
        $userId = $this->resolveUserId();
        return $this->crud->delete($id, $userId);
    }

    /**
     * Resuelve el ID del usuario autenticado.
     *
     * @return string ID del usuario
     *
     * @throws DomainException Si no hay usuario autenticado
     */
    private function resolveUserId(): string
    {
        $user = $this->roleUserService->resolveUser();

        if (!$user['authenticated']) {
            throw new DomainException('Usuario no autenticado.');
        }

        return (string) $user['id'];
    }
}
