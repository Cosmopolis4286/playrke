<?php

namespace App\Http\Controllers\Roles\Admin;

use App\DTOs\Roles\Admin\Customer\AdminCustomerDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\Admin\Customer\AdminCustomerRequest;
use App\Models\Roles\Customer;
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
        private readonly \App\Services\Roles\Admin\Customer\AdminCustomerService $service,
    ) {
        $this->authorizeResource(Customer::class, 'customer');
    }

    /**
     * Eliminar un cliente (soft delete).
     */
    public function delete(Customer $customer) : JsonResponse
    {
        $this->authorize('update', $customer);

        return $this->handleServiceJson(
            fn () => $this->service->delete($customer),
        );
    }

    /**
     * Eliminar un cliente permanentemente (hard delete).
     */
    public function destroy(Customer $customer) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->destroy($customer),
        );
    }

    /**
     * Listado de clientes
     */
    public function fetchList() : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => ['data' => $this->service->fetchList()],
        );
    }

    /**
     * Generar una nueva llave de conexión (customer_key).
     * Si se pasa un Customer, actualiza el registro; si no, devuelve una nueva llave temporal.
     */
    public function generateKey(\Illuminate\Http\Request $request) : JsonResponse
    {
        return $this->handleServiceJson(function() use ($request) {
            $id = $request->input('id');
            $customer = $id ? Customer::findOrFail($id) : null;

            if ($customer) {
                $this->authorize('update', $customer);
            } else {
                $this->authorize('create', Customer::class);
            }

            $key = $this->service->generateKey($customer);

            return ['key' => $key];
        });
    }

    /**
     * Generar una nueva contraseña para un cliente.
     */
    public function resetPassword(Customer $customer) : JsonResponse
    {
        $this->authorize('resetPassword', $customer);

        return $this->handleServiceJson(
            fn () => [
                'password' => $this->service->resetPassword($customer),
            ],
        );
    }

    /**
     * Mostrar detalle de un cliente.
     */
    public function show(Customer $customer) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => [
                'data' => $this->service->show($customer),
            ],
        );
    }

    /**
     * Crear un cliente.
     */
    public function store(AdminCustomerRequest $request) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => [
                'password' => $this->service->create(
                    $request->toDTO(AdminCustomerDTO::class),
                ),
            ],
        );
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) del cliente.
     */
    public function toggleStatus(Customer $customer) : JsonResponse
    {
        $this->authorize('toggleStatus', $customer);

        return $this->handleServiceJson(
            fn () => $this->service->toggleStatus($customer),
        );
    }

    /**
     * Actualizar un cliente.
     */
    public function update(AdminCustomerRequest $request, Customer $customer) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->update(
                $customer,
                $request->toDTO(AdminCustomerDTO::class),
            ),
        );
    }
}
