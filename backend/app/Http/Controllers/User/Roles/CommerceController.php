<?php

namespace App\Http\Controllers\User\Roles;

use App\Http\Controllers\Controller;

use App\Http\Requests\User\Roles\CommerceRequest;
use App\Services\User\Roles\Commerce\CommerceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

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
    public function store(CommerceRequest $request, CommerceService $service)
    {
        try {
            $result = $service->create($request->toDTO()->toArray());

            return response()->json([
                'success' => true,
                'message' => '✅ Registro exitoso',
                'password' => $result['password']
            ]);
        } catch (\Throwable $e) {
            Log::error("❌ Hubo un error al crear el Comercio: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Hubo un error al crear el Comercio.'
            ], 500);
        }
    }

    /**
     * Actualizar un Comercio existente.
     *
     * @param CommerceRequest $request
     * @param int $id
     * @param CommerceService $service
     * @return RedirectResponse
     */
    public function update(CommerceRequest $request, int $id, CommerceService $service): RedirectResponse
    {
        return $this->handleService(function () use ($request, $service, $id) {
            $service->update($request->toDTO(), $id);

            Session::flash('message', '✅ La información fue actualizada correctamente');

            return redirect()->back();
        }, "Hubo un error al actualizar el Comercio [ID: $id].");
    }

    /**
     * Eliminar físicamente un Comercio.
     *
     * @param string $id ID hasheado con Hashids
     * @return RedirectResponse
     */
    public function destroy(string $id): RedirectResponse
    {
        return $this->handleService(function () use ($id) {
            // Decodificar el ID usando Hashids
            $decodedIds = \Vinkla\Hashids\Facades\Hashids::connection('main')->decode($id);

            if (empty($decodedIds)) {
                throw new \Exception('ID inválido');
            }

            $realId = (string) $decodedIds[0];

            // Buscar el commerce con su usuario
            $commerce = \App\Models\Users\Roles\Commerce\Commerce::with('user')->findOrFail($realId);

            // Usar transacción para eliminar todo
            DB::transaction(function () use ($commerce) {
                $user = $commerce->user;

                // 1. Eliminar relaciones many-to-many del commerce
                $commerce->otherCurrencies()->detach();
                $commerce->allowedGames()->detach();

                // 2. Eliminar el commerce
                $commerce->delete();

                // 3. Eliminar wallets del usuario
                $user->wallets()->delete();

                // 4. Eliminar el usuario
                $user->delete();
            });

            Session::flash('message', '✅ Comercio eliminado correctamente');

            return redirect()->back();
        }, "No se pudo eliminar el Comercio.");
    }

    /**
     * Cambiar estado de un Comercio.
     *
     * @param string $id ID hasheado con Hashids
     * @return RedirectResponse
     */
    public function changeStatus(string $id): RedirectResponse
    {
        return $this->handleService(function () use ($id) {
            // Decodificar el ID usando Hashids
            $decodedIds = \Vinkla\Hashids\Facades\Hashids::connection('main')->decode($id);

            if (empty($decodedIds)) {
                throw new \Exception('ID inválido');
            }

            $realId = (string) $decodedIds[0];

            // Buscar el commerce con su usuario
            $commerce = \App\Models\Users\Roles\Commerce\Commerce::with('user')->findOrFail($realId);

            // Obtener el nuevo estado desde la request o usar DELETED por defecto
            $newStatus = request('status', 'DELETED');

            // Validar que el status sea válido
            $validStatuses = ['ACTIVE', 'INACTIVE', 'BLOCKED', 'DELETED'];
            if (!in_array($newStatus, $validStatuses)) {
                throw new \Exception('Estado inválido');
            }

            // Usar transacción para asegurar consistencia
            DB::transaction(function () use ($commerce, $newStatus) {
                // Cambiar estado del usuario asociado
                $commerce->user->update(['status' => $newStatus]);

                // Cambiar estado del commerce
                $commerce->update(['status' => $newStatus]);
            });

            $message = match ($newStatus) {
                'DELETED' => "✅ Comercio eliminado correctamente",
                'ACTIVE' => "✅ Comercio activado correctamente",
                'INACTIVE' => "✅ Comercio desactivado correctamente",
                'BLOCKED' => "✅ Comercio bloqueado correctamente",
                default => "✅ Estado cambiado correctamente"
            };

            Session::flash('message', $message);

            return redirect()->back();
        }, "Hubo un error al cambiar el estado del Comercio [ID: $id].");
    }

    /**
     * Cambiar contraseña de un Comercio.
     *
     * @param string $id ID hasheado con Hashids
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(string $id): \Illuminate\Http\JsonResponse
    {
        try {
            // Decodificar el ID usando Hashids
            $decodedIds = \Vinkla\Hashids\Facades\Hashids::connection('main')->decode($id);

            if (empty($decodedIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID inválido'
                ], 400);
            }

            $realId = (string) $decodedIds[0];

            // Buscar el commerce con su usuario
            $commerce = \App\Models\Users\Roles\Commerce\Commerce::with('user')->findOrFail($realId);

            // Generar una nueva contraseña aleatoria
            $newPassword = Str::random(12);

            // Actualizar la contraseña del usuario
            $commerce->user->update([
                'password' => Hash::make($newPassword)
            ]);

            return response()->json([
                'success' => true,
                'message' => '✅ Contraseña generada exitosamente',
                'password' => $newPassword
            ]);
        } catch (\Throwable $e) {
            Log::error("❌ Error al cambiar la contraseña del Comercio [ID: $id]: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => "Hubo un error al cambiar la contraseña del Comercio"
            ], 500);
        }
    }

    /**
     * Mostrar detalle de un Comercio.
     *
     * @param string $id ID hasheado con Hashids
     * @param CommerceService $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id, CommerceService $service)
    {
        try {
            // Decodificar el ID usando Hashids
            $decodedIds = \Vinkla\Hashids\Facades\Hashids::connection('main')->decode($id);

            if (empty($decodedIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID inválido'
                ], 400);
            }

            $realId = (string) $decodedIds[0];

            $commerce = $service->getById($realId);

            return response()->json([
                'success' => true,
                'data' => $commerce
            ]);
        } catch (\Exception $e) {
            Log::error('CommerceController::show - Error:', [
                'hashed_id' => $id,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => "No se pudo cargar la información del Comercio: {$e->getMessage()}"
            ], 500);
        }
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
