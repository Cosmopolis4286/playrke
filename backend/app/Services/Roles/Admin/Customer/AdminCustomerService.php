<?php

namespace App\Services\Roles\Admin\Customer;

use App\DTOs\Roles\Admin\Customer\AdminCustomerDTO;
use App\Http\Resources\Roles\Admin\Customer\AdminCustomerResource;
use App\Models\Roles\Customer;
use App\Services\User\UserPasswordService;
use App\Exceptions\Domain\Roles\Admin\Customer\AdminCustomerException;

/**
 * Servicio de aplicación para exponer
 * los casos de uso del dominio AdminCustomer.
 *
 * Responsabilidades:
 * - Orquestar casos de uso
 * - Delegar operaciones a servicios de dominio
 * - Mantener controllers desacoplados
 *
 * NO contiene lógica de dominio ni infraestructura.
 */
final class AdminCustomerService
{
    public function __construct(
        private readonly \App\Services\Roles\Admin\Customer\AdminCustomerContextService $contex,
        private readonly \App\Services\Roles\Admin\Customer\AdminCustomerCrudService $crud,
        private readonly UserPasswordService $userPasswordService
    ) {}

    /**
     * Obtener todos los clientes.
     *
     * @param int|null $managerUserId ID del usuario manager
     * @return array
     */
    public function fetchList(int $managerUserId): array
    {
        return $this->contex->resolveCustomers($managerUserId)['customers']['rows'] ?? [];
    }

    /**
     * Crear un comercio.
     *
     * @param AdminCustomerDTO $dto DTO con los datos del comercio
     * @return string Contraseña generada para el comercio
     *
     * @throws AdminCustomerException Si falla la creación
     */
    public function create(AdminCustomerDTO $dto): string
    {
        return $this->crud->create($dto);
    }

    /**
     * Obtener un comercio para visualización.
     *
     * @param Customer $customer Model
     * @return AdminCustomerResource Recurso para visualización
     *
     * @throws AdminCustomerException Si el comercio no es válido o no existe
     */
    public function show(Customer $customer): AdminCustomerResource
    {
        return $this->crud->show($customer);
    }

    /**
     * Actualizar un comercio.
     *
     * @param Customer $customer Model
     * @param AdminCustomerDTO $dto DTO con datos actualizados
     *
     * @throws AdminCustomerException Si falla la actualización
     */
    public function update(Customer $customer, AdminCustomerDTO $dto): void
    {
        $this->crud->update($customer, $dto);
    }

    /**
     * Eliminar un comercio (soft delete).
     *
     * @param Customer $customer Model
     *
     * @throws AdminCustomerException Si falla la eliminación
     */
    public function delete(Customer $customer): void
    {
        $this->crud->delete($customer);
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) del comercio.
     *
     * @param Customer $customer Model
     *
     * @throws AdminCustomerException Si falla el cambio de estado
     */
    public function toggleStatus(Customer $customer): void
    {
        $this->crud->toggleStatus($customer);
    }

    /**
     * Resetear la contraseña del comercio.
     *
     * @param Customer $customer Model
     * @return string Nueva contraseña generada
     *
     * @throws AdminCustomerException Si el comercio no está activo o falla el reseteo
     */
    public function resetPassword(Customer $customer): string
    {
        if ($customer->status !== 'ACTIVE') {
            throw AdminCustomerException::passwordResetRequiresActiveStatus();
        }

        return $this->userPasswordService->resetPassword($customer);
    }

    /**
     * Eliminar un comercio permanentemente (hard delete).
     *
     * @param Customer $customer Model
     *
     * @throws AdminCustomerException Si falla la eliminación definitiva
     */
    public function destroy(Customer $customer): void
    {
        $this->crud->destroy($customer);
    }

    /**
     * Generar una nueva llave de conexión (customer_key).
     *
     * Si se proporciona el modelo, actualiza el campo en BD y retorna la llave.
     * Si no, solo genera y retorna una llave sin persistir (para el formulario de creación).
     *
     * @param Customer|null $customer
     * @return string
     */
    public function generateKey(?Customer $customer): string
    {
        $key = bin2hex(random_bytes(32));

        if ($customer !== null) {
            $customer->update(['customer_key' => $key]);
        }

        return $key;
    }
}
