<?php

namespace App\Services\Directory;

use App\DTOs\Directories\Fiat\Venezuela\PagoMovilDTO;
use App\Http\Resources\Directories\Fiat\Venezuela\PagoMovilResource;
use App\Services\Security\UserContextService;
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
        private readonly UserContextService $userContext,
        private readonly DirectoryContextService $context,
        private readonly DirectoryCrudService $crud,
    ) {}

    /**
     * Obtener contexto para el directorio de acuerdo al codigo ISO.
     *
     * @param string|null $publicToken Token público opcional
     * @return array Contexto listo para la vista
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la obtención del contexto
     */
    public function context(?string $publicToken = null): array
    {
        $userId = $this->userContext->resolveUserId($publicToken);
        
        return $this->context->resolve($userId)->toView();
    }

    /**
     * Obtiene todos los registros de directorio del usuario.
     *
     * @param string|null $publicToken Token público opcional
     * @return array Listado de directorios
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la obtención de los regitros.
     */
    public function getAll(?string $publicToken = null): array
    {
        $userId = $this->userContext->resolveUserId($publicToken);

        return $this->context->getAll($userId);
    }

    /**
     * Crea un nuevo registro de directorio.
     *
     * @param PagoMovilDTO $dto DTO con los datos del registro
     * @return bool True si la creación fue exitosa
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la creación
     */
    public function create(PagoMovilDTO $dto): bool
    {
        $userId = $this->userContext->resolveUserId($dto->tokenId);

        return $this->crud->create($dto, $userId);
    }

    /**
     * Obtiene un registro específico de directorio.
     *
     * @param string $id ID del registro
     * @return PagoMovilResource Registro transformado
     *
     * @throws DomainException Si no se puede resolver el usuario o no existe el registro
     */
    public function show(string $id): PagoMovilResource
    {
        $userId = $this->userContext->resolveUserId();

        return $this->crud->show($id, $userId);
    }

    /**
     * Actualiza un registro de directorio existente.
     *
     * @param PagoMovilDTO $dto DTO con datos actualizados
     * @return bool True si se actualizó correctamente
     *
     * @throws DomainException Si falla la actualización
     */
    public function update(PagoMovilDTO $dto): bool
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
        $userId = $this->userContext->resolveUserId();
        return $this->crud->delete($id, $userId);
    }
}
