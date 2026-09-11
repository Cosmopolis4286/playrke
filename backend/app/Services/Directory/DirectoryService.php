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
 * - Resolver acceso admin (ver directorio de otro usuario, vía DirectoryAdminAccessService)
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
        private readonly DirectoryAdminAccessService $adminAccess,
    ) {}

    /**
     * Obtener contexto para el directorio de acuerdo al codigo ISO.
     *
     * @param string|null $publicToken Token público opcional
     * @param string $countryCode Código ISO2 del país (por defecto VE)
     * @param string|null $targetUserId Ver directorio de otro usuario (solo admin/superadmin)
     * @return array Contexto listo para la vista
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la obtención del contexto
     */
    public function context(?string $publicToken = null, string $countryCode = 'VE', ?string $targetUserId = null): array
    {
        $actorId = $this->userContext->resolveUserId($publicToken);
        $access = $this->adminAccess->resolve($actorId, $targetUserId);

        return $this->context->resolve($access['owner_user_id'], $countryCode, $access)->toView();
    }

    /**
     * Obtiene todos los registros de directorio del usuario según el país.
     *
     * @param string|null $publicToken Token público opcional
     * @param string $countryCode Código ISO2 del país (por defecto VE)
     * @param string|null $targetUserId Ver directorio de otro usuario (solo admin/superadmin)
     * @return array Listado de directorios
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la obtención de los regitros.
     */
    public function getAll(?string $publicToken = null, string $countryCode = 'VE', ?string $targetUserId = null): array
    {
        $actorId = $this->userContext->resolveUserId($publicToken);
        $access = $this->adminAccess->resolve($actorId, $targetUserId);

        return $this->context->getAll($access['owner_user_id'], $countryCode);
    }

    /**
     * Obtiene el contexto completo (bancos + documentTypes + directorios) para un país.
     * Usado por el endpoint AJAX al cambiar de tab.
     *
     * @param string|null $publicToken Token público opcional
     * @param string $countryCode Código ISO2 del país
     * @param string|null $targetUserId Ver directorio de otro usuario (solo admin/superadmin)
     * @return array
     *
     * @throws DomainException
     */
    public function getContextByCountry(?string $publicToken = null, string $countryCode = 'VE', ?string $targetUserId = null): array
    {
        $actorId = $this->userContext->resolveUserId($publicToken);
        $access = $this->adminAccess->resolve($actorId, $targetUserId);

        return $this->context->getContextByCountry($access['owner_user_id'], $countryCode);
    }

    /**
     * Crea un nuevo registro de directorio.
     *
     * @param PagoMovilDTO $dto DTO con los datos del registro
     * @param string|null $targetUserId Crear en nombre de otro usuario (solo admin/superadmin)
     * @return bool True si la creación fue exitosa
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la creación
     */
    public function create(PagoMovilDTO $dto, ?string $targetUserId = null): bool
    {
        $actorId = $this->userContext->resolveUserId($dto->tokenId);
        $access = $this->adminAccess->resolve($actorId, $targetUserId);

        return $this->crud->create($dto, (string) $access['owner_user_id'], (string) $access['actor_user_id'], $access['actor_role']);
    }

    /**
     * Obtiene un registro específico de directorio.
     *
     * @param string $id ID del registro
     * @param string|null $targetUserId Ver directorio de otro usuario (solo admin/superadmin)
     * @return PagoMovilResource Registro transformado
     *
     * @throws DomainException Si no se puede resolver el usuario o no existe el registro
     */
    public function show(string $id, ?string $targetUserId = null): PagoMovilResource
    {
        $actorId = $this->userContext->resolveUserId();
        $access = $this->adminAccess->resolve($actorId, $targetUserId);

        return $this->crud->show($id, (string) $access['owner_user_id']);
    }

    /**
     * Actualiza un registro de directorio existente.
     *
     * @param PagoMovilDTO $dto DTO con datos actualizados
     * @param string|null $targetUserId Editar en nombre de otro usuario (solo admin/superadmin)
     * @return bool True si se actualizó correctamente
     *
     * @throws DomainException Si falla la actualización
     */
    public function update(PagoMovilDTO $dto, ?string $targetUserId = null): bool
    {
        $actorId = $this->userContext->resolveUserId();
        $access = $this->adminAccess->resolve($actorId, $targetUserId);

        return $this->crud->update($dto, (string) $access['owner_user_id'], (string) $access['actor_user_id'], $access['actor_role']);
    }

    /**
     * Elimina un registro de directorio (soft delete) — VE.
     *
     * @param string $id ID del registro
     * @param string|null $targetUserId Eliminar en nombre de otro usuario (solo admin/superadmin)
     * @return bool True si se eliminó correctamente
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la eliminación
     */
    public function delete(string $id, ?string $targetUserId = null): bool
    {
        $actorId = $this->userContext->resolveUserId();
        $access = $this->adminAccess->resolve($actorId, $targetUserId);

        return $this->crud->delete($id, $access['owner_user_id'], (string) $access['actor_user_id'], $access['actor_role']);
    }

    /**
     * Elimina una clave PIX — BR.
     *
     * @param string $id ID de la clave PIX
     * @param string|null $targetUserId Eliminar en nombre de otro usuario (solo admin/superadmin)
     * @return bool True si se eliminó correctamente
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la eliminación
     */
    public function deletePixKey(string $id, ?string $targetUserId = null): bool
    {
        $actorId = $this->userContext->resolveUserId();
        $access = $this->adminAccess->resolve($actorId, $targetUserId);

        return $this->pixCrud->delete($id, (string) $access['owner_user_id'], (string) $access['actor_user_id'], $access['actor_role']);
    }

    /**
     * Obtiene una clave PIX por ID (Brasil).
     *
     * @param string $id ID de la clave PIX
     * @param string|null $targetUserId Ver directorio de otro usuario (solo admin/superadmin)
     * @return array Datos de la clave PIX
     *
     * @throws DomainException Si no se puede resolver el usuario o no existe la clave
     */
    public function showPixKey(string $id, ?string $targetUserId = null): array
    {
        $actorId = $this->userContext->resolveUserId();
        $access = $this->adminAccess->resolve($actorId, $targetUserId);

        return $this->pixCrud->show($id, (string) $access['owner_user_id']);
    }

    /**
     * Crea una nueva clave PIX (Brasil).
     *
     * @param PixKeyDTO $dto DTO con los datos de la clave PIX
     * @param string|null $targetUserId Crear en nombre de otro usuario (solo admin/superadmin)
     * @return bool True si la creación fue exitosa
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la creación
     */
    public function createPixKey(PixKeyDTO $dto, ?string $targetUserId = null): bool
    {
        $actorId = $this->userContext->resolveUserId($dto->tokenId);
        $access = $this->adminAccess->resolve($actorId, $targetUserId);

        return $this->pixCrud->create($dto, (string) $access['owner_user_id'], (string) $access['actor_user_id'], $access['actor_role']);
    }

    /**
     * Actualiza una clave PIX existente (Brasil).
     *
     * @param PixKeyDTO $dto DTO con los datos actualizados
     * @param string|null $targetUserId Editar en nombre de otro usuario (solo admin/superadmin)
     * @return bool True si la actualización fue exitosa
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la actualización
     */
    public function updatePixKey(PixKeyDTO $dto, ?string $targetUserId = null): bool
    {
        $actorId = $this->userContext->resolveUserId($dto->tokenId);
        $access = $this->adminAccess->resolve($actorId, $targetUserId);

        return $this->pixCrud->update($dto, (string) $access['owner_user_id'], (string) $access['actor_user_id'], $access['actor_role']);
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) del directorio — VE.
     *
     * @param string $id ID del registro de directorio
     * @param string|null $targetUserId Cambiar estado en nombre de otro usuario (solo admin/superadmin)
     *
     * @throws DomainException Si no se puede resolver el usuario o falla el cambio de estado
     */
    public function toggleStatus(string $id, ?string $targetUserId = null): bool
    {
        $actorId = $this->userContext->resolveUserId();
        $access = $this->adminAccess->resolve($actorId, $targetUserId);

        $pagoMovil = PagoMovil::findOrFail($id);

        return $this->crud->toggleStatus($pagoMovil, (string) $access['owner_user_id'], (string) $access['actor_user_id'], $access['actor_role']);
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) de una clave PIX — BR.
     *
     * @param string $id ID de la clave PIX
     * @param string|null $targetUserId Cambiar estado en nombre de otro usuario (solo admin/superadmin)
     * @return bool True si el cambio fue exitoso
     *
     * @throws DomainException Si no se puede resolver el usuario o falla el cambio de estado
     */
    public function togglePixKeyStatus(string $id, ?string $targetUserId = null): bool
    {
        $actorId = $this->userContext->resolveUserId();
        $access = $this->adminAccess->resolve($actorId, $targetUserId);

        return $this->pixCrud->toggleStatus($id, (string) $access['owner_user_id'], (string) $access['actor_user_id'], $access['actor_role']);
    }
}
