<?php

namespace App\Http\Controllers\Commerce;

use App\Http\Controllers\Controller;

use App\Http\Requests\CommerceRequest;

use App\Services\Commerce\CommerceService;
use App\Services\Commerce\CommerceUpdateService;
use App\Services\Commerce\CommerceDeleteService;
use App\Services\Commerce\CommerceStatusService;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Controlador HTTP para la gestión de Comercios (Commerce).
 *
 * - Maneja Requests y Responses HTTP.
 * - Toda la lógica de negocio se delega a los Services.
 */
class CommerceController extends Controller
{
    /**
     * Crear un nuevo Comercio.
     *
     * @param CommerceRequest $request
     * @param CommerceService $service
     * @return RedirectResponse
     */
    public function create(CommerceRequest $request, CommerceService $service): RedirectResponse
    {
        return $this->handleService(function () use ($request, $service) {
            $result = $service->create($request->toDTO());

            return redirect()->back()->with([
                'message' => '✅ Registro exitoso',
                'type'    => 'success',
                'passw'   => $result['password']
            ]);
        }, 'Hubo un error al crear el Comercio.');
    }

    /**
     * Actualizar un Comercio existente.
     *
     * @param CommerceRequest $request
     * @param int $id
     * @param CommerceUpdateService $service
     * @return RedirectResponse
     */
    public function update(CommerceRequest $request, int $id, CommerceUpdateService $service): RedirectResponse
    {
        return $this->handleService(function () use ($request, $service, $id) {
            $service->update($request->toDTO(), $id);

            Session::flash('message', '✅ La información fue actualizada correctamente');

            return redirect()->back();
        }, "Hubo un error al actualizar el Comercio [ID: $id].");
    }

    /**
     * Eliminar un Comercio.
     *
     * @param int $id
     * @param CommerceDeleteService $service
     * @return RedirectResponse
     */
    public function delete(int $id, CommerceDeleteService $service): RedirectResponse
    {
        return $this->handleService(function () use ($service, $id) {
            $service->delete($id);
            Session::flash('message', '✅ Comercio eliminado correctamente');

            return redirect()->back();
        }, "No se pudo eliminar el Comercio [ID: $id].");
    }

    /**
     * Cambiar estado (activo/inactivo) de un Comercio.
     *
     * @param int $id
     * @param CommerceStatusService $service
     * @return RedirectResponse
     */
    public function changeStatus(int $id, CommerceStatusService $service): RedirectResponse
    {
        return $this->handleService(function () use ($service, $id) {
            $service->toggleStatus($id);
            Session::flash('message', '✅ Estado actualizado correctamente');

            return redirect()->back();
        }, "Hubo un error al cambiar el estado del Comercio [ID: $id].");
    }

    /**
     * Mostrar detalle de un Comercio.
     *
     * @param int $id
     * @param CommerceService $service
     * @return \Illuminate\View\View|RedirectResponse
     */
    public function show(int $id, CommerceService $service)
    {
        return $this->handleService(function () use ($service, $id) {
            $commerce = $service->getById($id);
            return view('commerce.show', compact('commerce'));
        }, "No se pudo cargar la información del Comercio [ID: $id].");
    }

    /**
     * Listar todos los Comercios.
     *
     * @param CommerceService $service
     * @return \Illuminate\View\View|RedirectResponse
     */
    public function index(CommerceService $service)
    {
        return $this->handleService(function () use ($service) {
            $commerces = $service->getAll();
            return view('commerce.index', compact('commerces'));
        }, "No se pudo cargar la lista de Comercios.");
    }

    /**
     * Manejo centralizado de errores para los métodos del Controller.
     *
     * @param callable $callback
     * @param string $errorMessage
     * @return mixed
     */
    private function handleService(callable $callback, string $errorMessage)
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            Log::error("❌ $errorMessage Error: " . $e->getMessage());
            Session::flash('error', $errorMessage);

            return redirect()->back()->withErrors(['error' => $errorMessage]);
        }
    }
}
