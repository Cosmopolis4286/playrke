<?php

namespace App\Services\Roles\Admin\Commerce;

use App\DTOs\Roles\Admin\Commerce\AdminCommerceDTO;
use App\Exceptions\Domain\Roles\Admin\Commerce\AdminCommerceException;
use App\Http\Resources\Roles\Admin\Commerce\AdminCommerceResource;
use App\Models\Roles\Commerce;
use App\Services\Platform\PlatformConfigService;
use App\Services\User\UserPasswordService;

/**
 * Servicio de aplicación para exponer
 * los casos de uso del dominio AdminCommerce.
 *
 * Responsabilidades:
 * - Orquestar casos de uso
 * - Delegar operaciones a servicios de dominio
 * - Mantener controllers desacoplados
 *
 * NO contiene lógica de dominio ni infraestructura.
 */
final class AdminCommerceService
{
    public function __construct(
        private readonly \App\Services\Roles\Admin\Commerce\AdminCommerceContextService $contex,
        private readonly \App\Services\Roles\Admin\Commerce\AdminCommerceCrudService $crud,
        private readonly UserPasswordService $userPasswordService,
        private readonly PlatformConfigService $platformConfig,
    ) {
    }

    /**
     * Crear un comercio.
     *
     * @param AdminCommerceDTO $dto DTO con los datos del comercio
     *
     * @return string Contraseña generada para el comercio
     *
     * @throws AdminCommerceException Si falla la creación
     */
    public function create(AdminCommerceDTO $dto) : string
    {
        return $this->crud->create($dto);
    }

    /**
     * Eliminar un comercio (soft delete).
     *
     * @param Commerce $commerce Model
     *
     * @throws AdminCommerceException Si falla la eliminación
     */
    public function delete(Commerce $commerce) : void
    {
        $this->crud->delete($commerce);
    }

    /**
     * Eliminación lógica de varios comercios en una sola transacción.
     */
    public function deleteBulk(array $commerceIds) : array
    {
        return $this->crud->deleteBulk($commerceIds);
    }

    /**
     * Eliminar un comercio permanentemente (hard delete).
     *
     * @param Commerce $commerce Model
     *
     * @throws AdminCommerceException Si falla la eliminación definitiva
     */
    public function destroy(Commerce $commerce) : void
    {
        $this->crud->destroy($commerce);
    }

    /**
     * Cursor de todos los comercios que coincidan con los filtros, para CSV.
     */
    public function exportForCsv(array $filters = []) : \Illuminate\Support\LazyCollection
    {
        return $this->contex->exportForCsv($filters);
    }

    /**
     * Listado paginado y filtrado de comercios. $filters acepta: search,
     * country_name, status — ver AdminCommerceContextService::buildFilteredQuery().
     *
     * @return array{rows: array, pagination: array|null}
     */
    public function fetchList(array $filters = [], int $page = 1) : array
    {
        $data = $this->contex->resolveCommerces($filters, $page);

        return [
            'rows' => $data['commerces']['rows'] ?? [],
            'pagination' => $data['pagination'],
        ];
    }

    /**
     * Resetear la contraseña del comercio.
     *
     * @param Commerce $commerce Model
     *
     * @return string Nueva contraseña generada
     *
     * @throws AdminCommerceException Si el comercio no está activo o falla el reseteo
     */
    public function resetPassword(Commerce $commerce) : string
    {
        if ($commerce->status !== 'ACTIVE') {
            throw AdminCommerceException::passwordResetRequiresActiveStatus();
        }

        return $this->userPasswordService->resetPassword($commerce);
    }

    /**
     * Marcar el comercio como principal/vinculado a la app.
     *
     * @param Commerce $commerce Model
     *
     * @throws AdminCommerceException Si el comercio no está activo
     */
    public function setAsDefault(Commerce $commerce) : void
    {
        if ($commerce->status !== 'ACTIVE') {
            throw AdminCommerceException::invalidStatusChange();
        }

        $this->platformConfig->setDefaultCommerce($commerce);
    }

    /**
     * Obtener un comercio para visualización.
     *
     * @param Commerce $commerce Model
     *
     * @return AdminCommerceResource Recurso para visualización
     *
     * @throws AdminCommerceException Si el comercio no es válido o no existe
     */
    public function show(Commerce $commerce) : AdminCommerceResource
    {
        return $this->crud->show($commerce);
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) del comercio.
     *
     * @param Commerce $commerce Model
     *
     * @throws AdminCommerceException Si falla el cambio de estado
     */
    public function toggleStatus(Commerce $commerce) : void
    {
        $this->crud->toggleStatus($commerce);
    }

    /**
     * Alternar estado de varios comercios en una sola transacción.
     */
    public function toggleStatusBulk(array $commerceIds) : array
    {
        return $this->crud->toggleStatusBulk($commerceIds);
    }

    /**
     * Quitar la marca de comercio principal/vinculado a la app — la
     * plataforma queda sin comercio principal hasta que se marque otro. Solo
     * tiene efecto si $commerce es efectivamente el comercio marcado hoy;
     * si no lo es, no hace nada (evita desmarcar por error el comercio
     * equivocado si la marca cambió entre que se cargó la pantalla y se
     * confirmó la acción).
     *
     * @param Commerce $commerce Model
     *
     * @throws AdminCommerceException Si $commerce no es el comercio principal actual
     */
    public function unsetAsDefault(Commerce $commerce) : void
    {
        if (! $this->platformConfig->isDefaultCommerce((string) $commerce->id)) {
            throw AdminCommerceException::notDefaultCommerce();
        }

        $this->platformConfig->clearDefaultCommerce();
    }

    /**
     * Actualizar un comercio.
     *
     * @param Commerce         $commerce Model
     * @param AdminCommerceDTO $dto      DTO con datos actualizados
     *
     * @throws AdminCommerceException Si falla la actualización
     */
    public function update(Commerce $commerce, AdminCommerceDTO $dto) : void
    {
        $this->crud->update($commerce, $dto);
    }
}
