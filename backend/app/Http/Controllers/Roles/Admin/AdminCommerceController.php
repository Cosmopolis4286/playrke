<?php

namespace App\Http\Controllers\Roles\Admin;

use App\DTOs\Roles\Admin\Commerce\AdminCommerceDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\Admin\Commerce\AdminCommerceRequest;
use App\Models\Roles\Commerce;
use App\Services\Roles\Admin\Commerce\AdminCommerceService;
use Illuminate\Http\JsonResponse;

class AdminCommerceController extends Controller
{
    /**
     * @param AdminCommerceService $service
     */
    public function __construct(
        protected AdminCommerceService $service
    ) {
        $this->authorizeResource(Commerce::class, 'commerce');
    }

    /**
     * Crear un comercio.
     *
     * @param AdminCommerceRequest $request
     * @return JsonResponse
     */
    public function store(AdminCommerceRequest $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'message'  => 'Registro exitoso',
                'password' => $this->service
                    ->create($request->toDTO(AdminCommerceDTO::class))['password'],
            ],
            'Hubo un error al crear el comercio.'
        );
    }

    /**
     * Mostrar detalle de un comercio.
     *
     * @param Commerce $commerce
     * @return JsonResponse
     */
    public function show(Commerce $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'data' => $this->service->show($commerce),
            ],
            'No se pudo cargar la información del comercio.'
        );
    }

    /**
     * Actualizar un comercio.
     *
     * @param AdminCommerceRequest $request
     * @param Commerce $commerce
     * @return JsonResponse
     */
    public function update(AdminCommerceRequest $request, Commerce $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => tap(
                $this->service->update(
                    $commerce,
                    $request->toDTO(AdminCommerceDTO::class)
                ),
                fn() => ['message' => 'Actualización exitosa']
            ) ?: false,
            'Hubo un error al editar el comercio.'
        );
    }

    /**
     * Eliminar un comercio (soft delete).
     *
     * @param Commerce $commerce
     * @return JsonResponse
     */
    public function delete(Commerce $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => tap(
                $this->service->delete(
                    $commerce,
                    'DELETED',
                    ['currencies', 'games'],
                    'user'
                ),
                fn() => ['message' => 'Comercio eliminado correctamente']
            ) ?: false,
            'No se pudo eliminar el comercio.'
        );
    }

    /**
     * Eliminar un comercio permanentemente (hard delete).
     *
     * @param Commerce $commerce
     * @return JsonResponse
     */
    public function destroy(Commerce $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => tap(
                $this->service->destroy($commerce),
                fn() => ['message' => 'Comercio eliminado correctamente']
            ) ?: false,
            'No se pudo eliminar el comercio.'
        );
    }

    /**
     * Cambiar el estado de un comercio.
     *
     * @param Commerce $commerce
     * @return JsonResponse
     */
    public function changeStatus(Commerce $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => tap(
                $this->service->toggleStatus($commerce),
                fn() => ['message' => 'Estado actualizado correctamente']
            ) ?: false,
            'No se pudo cambiar el estado del comercio.'
        );
    }

    /**
     * Generar una nueva contraseña para un comercio.
     *
     * @param Commerce $commerce
     * @return JsonResponse
     */
    public function resetPassword(Commerce $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'message'  => 'Contraseña generada exitosamente',
                'password' => $this->service->resetPassword($commerce),
            ],
            'No se pudo cambiar la contraseña del comercio.'
        );
    }
}
