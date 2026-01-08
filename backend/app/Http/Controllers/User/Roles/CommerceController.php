<?php

namespace App\Http\Controllers\User\Roles;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Roles\CommerceRequest;
use App\Models\Users\Roles\Commerce\Commerce;
use App\Services\User\Roles\Commerce\CommerceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Vinkla\Hashids\Facades\Hashids;

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
            return view('commerce.index', [
                'commerces' => $this->service->getAll(),
            ]);
        }, 'No se pudo cargar la lista de comercios.');
    }

    /**
     * Crear un comercio
     */
    public function store(CommerceRequest $request): JsonResponse
    {
        return $this->handleServiceJson(function () use ($request) {

            $result = $this->service->create(
                $request->toDTO()->toArray()
            );

            return [
                'message'  => '✅ Registro exitoso',
                'password' => $result['password'],
            ];
        }, 'Hubo un error al crear el comercio.');
    }

    /**
     * Actualizar un comercio
     */
    public function update(CommerceRequest $request, Commerce $commerce): JsonResponse
    {
        return $this->handleServiceJson(function () use ($request, $commerce) {

            $this->service->update(
                $commerce->id,
                $request->toDTO()->toArray()
            );

            return [
                'message' => '✅ Actualización exitosa',
            ];
        }, 'Hubo un error al editar el comercio.');
    }

    /**
     * Eliminar comercio (hard delete)
     */
    public function destroy(string $id): RedirectResponse
    {
        return $this->handleService(function () use ($id) {

            $this->service->delete(
                $this->decodeHashId($id)
            );

            Session::flash('message', '✅ Comercio eliminado correctamente');

            return redirect()->back();
        }, 'No se pudo eliminar el comercio.');
    }

    /**
     * Mostrar detalle de comercio
     */
    public function show(Commerce $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'data' => $this->service->getById($commerce->id),
            ],
            'No se pudo cargar la información del comercio.'
        );
    }

    /**
     * Cambiar estado de comercio
     */
    public function changeStatus(string $id): JsonResponse
    {
        return $this->handleServiceJson(function () use ($id) {

            $this->service->changeStatus(
                $this->decodeHashId($id),
                request()->string('status')->value()
            );

            return [
                'message' => '✅ Estado actualizado correctamente',
            ];
        }, 'No se pudo cambiar el estado del comercio.');
    }

    /**
     * Generar nueva contraseña para comercio
     */
    public function changePassword(string $id): JsonResponse
    {
        return $this->handleServiceJson(function () use ($id) {

            $password = $this->service->resetPassword(
                $this->decodeHashId($id)
            );

            return [
                'message'  => '✅ Contraseña generada exitosamente',
                'password' => $password,
            ];
        }, 'No se pudo cambiar la contraseña del comercio.');
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

    /**
     * ==========================
     * Helpers privados
     * ==========================
     */

    private function decodeHashId(string $hashedId): string
    {
        $decoded = Hashids::connection('main')->decode($hashedId);

        if (empty($decoded)) {
            abort(400, 'ID inválido');
        }

        return (string) $decoded[0];
    }
}
