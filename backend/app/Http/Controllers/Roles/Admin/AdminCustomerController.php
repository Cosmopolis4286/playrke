<?php

namespace App\Http\Controllers\Roles\Admin;

use App\Http\Controllers\Controller;

use App\Models\Roles\Customer;
use App\DTOs\Roles\Admin\Customer\AdminCustomerDTO;
use App\Http\Requests\Roles\Admin\Customer\AdminCustomerRequest;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de administración de Clientes.
 *
 * Responsabilidades:
 * - Orquestar requests HTTP
 * - Delegar lógica de negocio al servicio
 * - Unificar respuestas JSON
 *
 * NO contiene lógica de dominio ni mensajes de error.
 */
final class AdminCustomerController extends Controller
{
    /**
     * @param AdminCustomerService $service
     */
    public function __construct(
        private readonly \App\Services\Roles\Admin\Customer\AdminCustomerService $service
    ) {
        $this->authorizeResource(Customer::class, 'customer');
    }

    /**
     * Crear un cliente.
     *
     * @param AdminCustomerRequest $request
     * @return JsonResponse
     */
    public function store(AdminCustomerRequest $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'password' => $this->service->create(
                    $request->toDTO(AdminCustomerDTO::class)
                ),
            ]
        );
    }

    /**
     * Listado de clientes
     *
     * @return JsonResponse
     */
    public function listFiltered(): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => ['data' => $this->service->listFiltered()]
        );
    }

    /**
     * Mostrar detalle de un cliente.
     *
     * @param Customer $customer
     * @return JsonResponse
     */
    public function show(Customer $customer): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'data' => $this->service->show($customer),
            ]
        );
    }

    /**
     * Actualizar un cliente.
     *
     * @param AdminCustomerRequest $request
     * @param Customer $customer
     * @return JsonResponse
     */
    public function update(AdminCustomerRequest $request, Customer $customer): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->update(
                $customer,
                $request->toDTO(AdminCustomerDTO::class)
            )
        );
    }

    /**
     * Eliminar un cliente (soft delete).
     *
     * @param Customer $customer
     * @return JsonResponse
     */
    public function delete(Customer $customer): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->delete($customer)
        );
    }

    /**
     * Eliminar un cliente permanentemente (hard delete).
     *
     * @param Customer $customer
     * @return JsonResponse
     */
    public function destroy(Customer $customer): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->destroy($customer)
        );
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) del cliente.
     *
     * @param Customer $customer
     * @return JsonResponse
     */
    public function toggleStatus(Customer $customer): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->toggleStatus($customer)
        );
    }

    /**
     * Generar una nueva contraseña para un cliente.
     *
     * @param Customer $customer
     * @return JsonResponse
     */
    public function resetPassword(Customer $customer): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'password' => $this->service->resetPassword($customer),
            ]
        );
    }
}
