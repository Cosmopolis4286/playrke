<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommerceRequest;
use App\Http\Requests\CommerceUpdateRequest;
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
 * Este controlador solo maneja Requests y Responses HTTP,
 * delegando toda la lógica de negocio a los Services.
 */
class CommerceController extends Controller
{
    /**
     * Crear nueva Comercializadora (Commerce).
     *
     * @param CommerceRequest  $request
     * @param CommerceService  $service
     * @return RedirectResponse
     */
    public function create(CommerceRequest $request, CommerceService $service): RedirectResponse
    {
        try {
            $result = $service->create($request->toDTO());

            return redirect()->back()->with([
                'message' => '✅ Registro exitoso',
                'type'    => 'success',
                'passw'   => $result['password']
            ]);
        } catch (\Throwable $e) {
            Log::error('❌ Error al crear Commerce: ' . $e->getMessage());

            return back()->withErrors(['error' => 'Hubo un error al procesar la solicitud.']);
        }
    }

    /**
     * Actualizar información de una Comercializadora.
     *
     * @param CommerceUpdateRequest $request
     * @param int                   $id
     * @param CommerceUpdateService $service
     * @return RedirectResponse
     */
    public function update(CommerceUpdateRequest $request, int $id, CommerceUpdateService $service): RedirectResponse
    {
        try {
            $service->update($request->toDTO(), $id);

            Session::flash('message', '✅ La información fue actualizada correctamente');
        } catch (\Throwable $e) {
            Log::error("❌ Error al actualizar Commerce [ID: $id]: " . $e->getMessage());
            Session::flash('error', 'Hubo un error al actualizar el Comercio.');
        }

        return redirect()->back();
    }

    /**
     * Eliminar una Comercializadora.
     *
     * @param int                  $id
     * @param CommerceDeleteService $service
     * @return RedirectResponse
     */
    public function delete(int $id, CommerceDeleteService $service): RedirectResponse
    {
        try {
            $service->delete($id);

            Session::flash('message', '✅ Comercio eliminado correctamente');
        } catch (\Throwable $e) {
            Log::error("❌ Error al eliminar Commerce [ID: $id]: " . $e->getMessage());
            Session::flash('error', 'No se pudo eliminar el Comercio.');
        }

        return redirect()->back();
    }

    /**
     * Cambiar el estado (activo/inactivo) de una Comercializadora.
     *
     * @param int                   $id
     * @param CommerceStatusService $service
     * @return RedirectResponse
     */
    public function changeStatus(int $id, CommerceStatusService $service): RedirectResponse
    {
        try {
            $service->toggleStatus($id);

            Session::flash('message', '✅ Estado actualizado correctamente');
        } catch (\Throwable $e) {
            Log::error("❌ Error al cambiar estado de Commerce [ID: $id]: " . $e->getMessage());
            Session::flash('error', 'Hubo un error al cambiar el estado del Comercio.');
        }

        return redirect()->back();
    }

    /**
     * Mostrar detalle de una Comercializadora.
     *
     * @param int              $id
     * @param CommerceService  $service
     * @return \Illuminate\View\View|RedirectResponse
     */
    public function show(int $id, CommerceService $service)
    {
        try {
            $commerce = $service->getById($id);

            return view('commerce.show', compact('commerce'));
        } catch (\Throwable $e) {
            Log::error("❌ Error al mostrar Commerce [ID: $id]: " . $e->getMessage());

            return redirect()->back()->withErrors(['error' => 'No se pudo cargar la información del Comercio.']);
        }
    }

    /**
     * Listar todas las Comercializadoras.
     *
     * @param CommerceService $service
     * @return \Illuminate\View\View|RedirectResponse
     */
    public function index(CommerceService $service)
    {
        try {
            $commerces = $service->getAll();

            return view('commerce.index', compact('commerces'));
        } catch (\Throwable $e) {
            Log::error("❌ Error al listar Commerces: " . $e->getMessage());

            return redirect()->back()->withErrors(['error' => 'No se pudo cargar la lista de Comercios.']);
        }
    }
}
