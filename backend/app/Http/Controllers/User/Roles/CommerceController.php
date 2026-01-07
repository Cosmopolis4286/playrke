<?php

namespace App\Http\Controllers\User\Roles;

use App\Http\Controllers\Controller;

use App\Http\Requests\User\Roles\CommerceRequest;
use App\Models\Users\Roles\Commerce\Commerce;
use App\Services\User\Roles\Commerce\CommerceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Controlador HTTP para la gestión de Comercios (Commerce).
 *
 * - Maneja Requests y Responses HTTP.
 * - Toda la lógica de negocio se delega a los Services.
 */
class CommerceController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Commerce::class, 'commerce');
    }

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

            // Validar que el comercio no esté ya eliminado (DELETED)
            if ($commerce->status === 'DELETED') {
                throw new \Exception('No se puede eliminar un comercio que ya está eliminado');
            }

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
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeStatus(string $id): \Illuminate\Http\JsonResponse
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
            $commerce = \App\Models\Users\Roles\Commerce\Commerce::with('user')
                ->findOrFail($realId);

            // Obtener el nuevo estado desde la request
            $newStatus = request('status', 'DELETED');

            // Validar estado
            $validStatuses = ['ACTIVE', 'INACTIVE', 'BLOCKED', 'DELETED'];
            if (!in_array($newStatus, $validStatuses, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estado inválido'
                ], 400);
            }

            // Transacción
            DB::transaction(function () use ($commerce, $newStatus) {
                $commerce->user->update([
                    'status' => $newStatus,
                ]);

                $commerce->update([
                    'status' => $newStatus,
                ]);
            });

            $message = match ($newStatus) {
                'DELETED'  => '✅ Comercio eliminado correctamente',
                'ACTIVE'   => '✅ Comercio activado correctamente',
                'INACTIVE' => '✅ Comercio desactivado correctamente',
                'BLOCKED'  => '✅ Comercio bloqueado correctamente',
                default    => '✅ Estado cambiado correctamente',
            };

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Throwable $e) {
            // Log detallado para debugging
            Log::error('Error al cambiar estado de comercio', [
                'hashed_id' => $id,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => "Hubo un error al cambiar el estado del Comercio"
            ], 500);
        }
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
     * @param Commerce $commerce  Modelo resuelto vía Route Model Binding (Hashids)
     * @param CommerceService $service
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Commerce $commerce, CommerceService $service)
    {
        try {
            $commerceData = $service->getById($commerce->id);

            return response()->json([
                'success' => true,
                'data' => $commerceData,
            ]);
        } catch (\Throwable $e) {
            \Log::error('CommerceController::show error', [
                'commerce_id' => $commerce->id,
                'hashed_id'   => $commerce->hashed_id,
                'exception'  => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo cargar la información del comercio.',
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
     * Vista de vendedores
     */
    public function sellers(): View
    {
        $sellers = $this->commerces->sellers->getAllWithRelations([
            'country:id,name',
            'user:id,email',
        ]);

        return view('modules.users.roles.seller.form.index', [
            'title' => 'Vendedores',
            'pageData' => [
                // 'currencies' => $this->currencies->getActive(),
                // 'commerces'  => $commerces,
                // 'countries' => $this->countries->getActive(),
                // 'games'     => $this->games->getActive(),
                // 'timeZones' => $this->timeZones->getActive(),
                'enableFormCrud' => true,
                'moduleFile' => 'modules/users/roles/admin/commerce/form/commerceForm.ts',
            ]
        ]);
    }
}
