<?php

namespace App\Http\Controllers\Roles;

use App\DTOs\Roles\Admin\Commerce\AdminCommerceDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\CommerceRequest;
use App\Models\Roles\Commerce;
use App\Services\Roles\Commerce\CommerceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommerceController extends Controller
{
    public function __construct(
        protected CommerceService $service
    ) {
        $this->authorizeResource(Commerce::class, 'commerce');
    }

    /**
     * Listado de comercios
     */
    public function index(): View|RedirectResponse
    {
        return $this->handleService(function () {
            return view('commerce.index', ['commerces' => $this->service->getAll()]);
        }, 'No se pudo cargar la lista de comercios.');
    }

    /**
     * Crear un comercio
     */
    public function store(CommerceRequest $request): JsonResponse
    {
        return $this->handleServiceJson(function () use ($request) {

            $result = $this->service->create($request->toDTO(AdminCommerceDTO::class));

            return [
                'message'  => '✅ Registro exitoso',
                'password' => $result['password'],
            ];
        }, 'Hubo un error al crear el comercio.');
    }

    /**
     * Mostrar detalle de comercio
     */
    public function show(Commerce $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => ['data' => $this->service->getByCommerce($commerce)],
            'No se pudo cargar la información del comercio.'
        );
    }

    /**
     * Actualizar un comercio
     * @param CommerceRequest $request
     * @param Commerce $commerce
     * @return JsonResponse
     */
    public function update(CommerceRequest $request, Commerce $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            function () use ($request, $commerce) {

                $this->service->update($commerce, $request->toDTO(AdminCommerceDTO::class));

                return ['message' => '✅ Actualización exitosa'];
            },
            'Hubo un error al editar el comercio.'
        );
    }

    /**
     * Eliminar comercio (soft delete)
     * @param Commerce $commerce
     * @return JsonResponse
     */
    public function delete(Commerce $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            function () use ($commerce) {
                $this->service->softDelete(
                    $commerce,
                    'DELETED',
                    ['currencies', 'games'],
                    'user'
                );
                return ['message' => '✅ Comercio eliminado correctamente'];
            },
            'No se pudo eliminar el comercio.'
        );
    }

    /**
     * Eliminar comercio (hard delete)
     * @param Commerce $commerce
     * @return JsonResponse
     */
    public function destroy(Commerce $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            function () use ($commerce) {
                $this->service->hardDelete($commerce);
                return ['message' => '✅ Comercio eliminado correctamente'];
            },
            'No se pudo eliminar el comercio.'
        );
    }

    /**
     * Cambiar el estado de un comercio.
     *
     * @param Commerce $commerce
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function changeStatus(Commerce $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            function () use ($commerce) {
                $this->service->changeStatus($commerce);
                return ['message' => '✅ Estado actualizado correctamente',];
            },
            'No se pudo cambiar el estado del comercio.'
        );
    }

    /**
     * Generar una nueva contraseña para un comercio.
     *
     * @param Commerce $commerce Modelo resuelto vía Route Model Binding (Hashids)
     *
     * @return JsonResponse
     */
    public function resetPassword(Commerce $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            function () use ($commerce) {
                $password = $this->service->resetPassword($commerce);

                return [
                    'message'  => '✅ Contraseña generada exitosamente',
                    'password' => $password,
                ];
            },
            'No se pudo cambiar la contraseña del comercio.'
        );
    }

    /**
     * Vista de vendedores
     */
    public function sellers(): View
    {
        return view('modules.users.roles.seller.form.index', [
            'title' => 'Vendedores',
            'pageData' => [
                'enableFormCrud' => true,
                'moduleFile' => 'modules/users/roles/admin/commerce/form/commerceForm.ts',
            ],
        ]);
    }
}
