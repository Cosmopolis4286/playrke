<?php

namespace App\Services\Directory;

use App\DTOs\Directories\Fiat\Brazil\PixKeyDTO;
use App\DTOs\Directories\Fiat\Venezuela\PagoMovilDTO;
use App\Http\Resources\Directories\Fiat\Venezuela\PagoMovilResource;
use App\Models\Directories\Fiat\Venezuela\PagoMovil;
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
        private readonly PixKeyCrudService $pixCrud,
    ) {}

    /**
     * Obtener contexto para el directorio de acuerdo al codigo ISO.
     *
     * @param string|null $publicToken Token público opcional
     * @param string $countryCode Código ISO2 del país (por defecto VE)
     * @return array Contexto listo para la vista
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la obtención del contexto
     */
    public function context(?string $publicToken = null, string $countryCode = 'VE'): array
    {
        $userId = $this->userContext->resolveUserId($publicToken);

        return $this->context->resolve($userId, $countryCode)->toView();
    }

    /**
     * Obtiene todos los registros de directorio del usuario según el país.
     *
     * @param string|null $publicToken Token público opcional
     * @param string $countryCode Código ISO2 del país (por defecto VE)
     * @return array Listado de directorios
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la obtención de los regitros.
     */
    public function getAll(?string $publicToken = null, string $countryCode = 'VE'): array
    {
        $userId = $this->userContext->resolveUserId($publicToken);

        return $this->context->getAll($userId, $countryCode);
    }

    /**
     * Obtiene el contexto completo (bancos + documentTypes + directorios) para un país.
     * Usado por el endpoint AJAX al cambiar de tab.
     *
     * @param string|null $publicToken Token público opcional
     * @param string $countryCode Código ISO2 del país
     * @return array
     *
     * @throws DomainException
     */
    public function getContextByCountry(?string $publicToken = null, string $countryCode = 'VE'): array
    {
        $userId = $this->userContext->resolveUserId($publicToken);

        return $this->context->getContextByCountry($userId, $countryCode);
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

    /**
     * Crea una nueva clave PIX (Brasil).
     *
     * @param PixKeyDTO $dto DTO con los datos de la clave PIX
     * @return bool True si la creación fue exitosa
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la creación
     */
    public function createPixKey(PixKeyDTO $dto): bool
    {
        $userId = $this->userContext->resolveUserId($dto->tokenId);

        return $this->pixCrud->create($dto, $userId);
    }

    /**
     * Actualiza una clave PIX existente (Brasil).
     *
     * @param PixKeyDTO $dto DTO con los datos actualizados
     * @return bool True si la actualización fue exitosa
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la actualización
     */
    public function updatePixKey(PixKeyDTO $dto): bool
    {
        $userId = $this->userContext->resolveUserId($dto->tokenId);

        return $this->pixCrud->update($dto, $userId);
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) del directorio.
     *
     * @param string $id ID del registro de directorio
     *
     * @throws DomainException Si no se puede resolver el usuario o falla el cambio de estado
     */
    public function toggleStatus(string $id): bool
    {
        $pagoMovil = PagoMovil::findOrFail($id);

        return $this->crud->toggleStatus($pagoMovil);
    }
}
