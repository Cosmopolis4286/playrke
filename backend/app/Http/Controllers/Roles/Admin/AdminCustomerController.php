<?php

namespace App\Http\Controllers\Roles\Admin;

use App\DTOs\Roles\Admin\Customer\AdminCustomerDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\Admin\Customer\AdminCustomerRequest;
use App\Models\Roles\Customer;
use App\Services\Roles\Admin\Customer\AdminCustomerService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de administración de Comercios.
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
        protected readonly AdminCustomerService $service
    ) {
        $this->authorizeResource(Customer::class, 'commerce');
    }

    /**
     * Crear un comercio.
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
     * Listado de comercios
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
     * Mostrar detalle de un comercio.
     *
     * @param Customer $commerce
     * @return JsonResponse
     */
    public function show(Customer $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'data' => $this->service->show($commerce),
            ]
        );
    }

    /**
     * Actualizar un comercio.
     *
     * @param AdminCustomerRequest $request
     * @param Customer $commerce
     * @return JsonResponse
     */
    public function update(AdminCustomerRequest $request, Customer $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->update(
                $commerce,
                $request->toDTO(AdminCustomerDTO::class)
            )
        );
    }

    /**
     * Eliminar un comercio (soft delete).
     *
     * @param Customer $commerce
     * @return JsonResponse
     */
    public function delete(Customer $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->delete($commerce)
        );
    }

    /**
     * Eliminar un comercio permanentemente (hard delete).
     *
     * @param Customer $commerce
     * @return JsonResponse
     */
    public function destroy(Customer $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->destroy($commerce)
        );
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) del comercio.
     *
     * @param Customer $commerce
     * @return JsonResponse
     */
    public function toggleStatus(Customer $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->toggleStatus($commerce)
        );
    }

    /**
     * Generar una nueva contraseña para un comercio.
     *
     * @param Customer $commerce
     * @return JsonResponse
     */
    public function resetPassword(Customer $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'password' => $this->service->resetPassword($commerce),
            ]
        );
    }
}
