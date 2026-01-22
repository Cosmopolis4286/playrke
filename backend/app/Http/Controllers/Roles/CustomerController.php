<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\Roles\CustomerRequest;
use App\Services\Users\Roles\Customer\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

/**
 * Controlador HTTP para la gestión de Clientes (Customer).
 *
 * - Maneja Requests y Responses HTTP.
 * - Toda la lógica de negocio se delega a los Services.
 * - Sigue el patrón de arquitectura establecido en el proyecto.
 *
 * Responsabilidades:
 * - Validar entrada mediante CustomerRequest
 * - Delegar operaciones a CustomerService
 * - Retornar respuestas HTTP apropiadas
 * - Manejar errores y logging
 *
 * @package App\Http\Controllers\Roles
 */
class CustomerController extends Controller
{
    /**
     * Crear un nuevo Cliente.
     *
     * Proceso:
     * 1. Valida los datos mediante CustomerRequest
     * 2. Delega la creación al CustomerService
     * 3. Retorna respuesta JSON con la contraseña generada
     *
     * @param CustomerRequest $request Request validado con los datos del cliente
     * @param CustomerService $service Servicio de lógica de negocio
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con el resultado
     */
    public function store(CustomerRequest $request, CustomerService $service)
    {
        try {
            $result = $service->create($request->toDTO()->toArray());

            return response()->json([
                'success' => true,
                'message' => '✅ Registro exitoso',
                'password' => $result['password']
            ]);
        } catch (\Throwable $e) {
            Log::error("❌ Hubo un error al crear el Cliente: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Hubo un error al crear el Cliente.'
            ], 500);
        }
    }

    /**
     * Actualizar un Cliente existente.
     *
     * Proceso:
     * 1. Valida los datos mediante CustomerRequest
     * 2. Decodifica el ID hasheado
     * 3. Delega la actualización al CustomerService
     * 4. Retorna redirect con mensaje flash
     *
     * @param CustomerRequest $request Request validado con los datos
     * @param int $id ID hasheado del cliente con Hashids
     * @param CustomerService $service Servicio de lógica de negocio
     * @return RedirectResponse Redirect con mensaje flash
     */
    public function update(CustomerRequest $request, int $id, CustomerService $service): RedirectResponse
    {
        return $this->handleService(function () use ($request, $service, $id) {
            $service->update($request->toDTO()->toArray(), $id);

            Session::flash('message', '✅ La información fue actualizada correctamente');

            return redirect()->back();
        }, "Hubo un error al actualizar el Cliente [ID: $id].");
    }

    /**
     * Eliminar físicamente un Cliente.
     *
     * Elimina el cliente, su usuario asociado, wallets y relaciones many-to-many.
     *
     * @param string $id ID hasheado con Hashids
     * @return RedirectResponse Redirect con mensaje flash
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

            // Buscar el customer con su usuario
            $customer = \App\Models\Roles\Customer::with('user')->findOrFail($realId);

            // Usar transacción para eliminar todo
            DB::transaction(function () use ($customer) {
                $user = $customer->user;

                // 1. Eliminar relaciones many-to-many del customer
                $customer->otherCurrencies()->detach();
                $customer->allowedGames()->detach();

                // 2. Eliminar el customer
                $customer->delete();

                // 3. Eliminar wallets del usuario
                $user->wallets()->delete();

                // 4. Eliminar el usuario
                $user->delete();
            });

            Session::flash('message', '✅ Cliente eliminado correctamente');

            return redirect()->back();
        }, "No se pudo eliminar el Cliente.");
    }

    /**
     * Cambiar estado de un Cliente.
     *
     * Cambia el estado tanto del customer como del usuario asociado.
     * Estados permitidos: ACTIVE, INACTIVE, BLOCKED, DELETED
     *
     * @param string $id ID hasheado con Hashids
     * @return RedirectResponse Redirect con mensaje flash
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

            // Buscar el customer con su usuario
            $customer = \App\Models\Roles\Customer::with('user')->findOrFail($realId);

            // Obtener el nuevo estado desde la request o usar DELETED por defecto
            $newStatus = request('status', 'DELETED');

            // Validar que el status sea válido
            $validStatuses = ['ACTIVE', 'INACTIVE', 'BLOCKED', 'DELETED'];
            if (!in_array($newStatus, $validStatuses)) {
                throw new \Exception('Estado inválido');
            }

            // Usar transacción para asegurar consistencia
            DB::transaction(function () use ($customer, $newStatus) {
                // Cambiar estado del usuario asociado
                $customer->user->update(['status' => $newStatus]);

                // Cambiar estado del customer
                $customer->update(['status' => $newStatus]);
            });

            $message = match ($newStatus) {
                'DELETED' => "✅ Cliente eliminado correctamente",
                'ACTIVE' => "✅ Cliente activado correctamente",
                'INACTIVE' => "✅ Cliente desactivado correctamente",
                'BLOCKED' => "✅ Cliente bloqueado correctamente",
                default => "✅ Estado cambiado correctamente"
            };

            Session::flash('message', $message);

            return redirect()->back();
        }, "Hubo un error al cambiar el estado del Cliente [ID: $id].");
    }

    /**
     * Cambiar contraseña de un Cliente.
     *
     * Genera una nueva contraseña aleatoria de 8 caracteres hexadecimales
     * y la actualiza en el usuario asociado al cliente.
     *
     * @param string $id ID hasheado con Hashids
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con la nueva contraseña
     */
    public function resetPassword(string $id): \Illuminate\Http\JsonResponse
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

            // Buscar el customer con su usuario
            $customer = \App\Models\Roles\Customer::with('user')->findOrFail($realId);

            // Generar una nueva contraseña aleatoria
            $newPassword = Str::random(12);

            // Actualizar la contraseña del usuario
            $customer->user->update([
                'password' => Hash::make($newPassword)
            ]);

            return response()->json([
                'success' => true,
                'message' => '✅ Contraseña generada exitosamente',
                'password' => $newPassword
            ]);
        } catch (\Throwable $e) {
            Log::error("❌ Error al cambiar la contraseña del Cliente [ID: $id]: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => "Hubo un error al cambiar la contraseña del Cliente"
            ], 500);
        }
    }

    /**
     * Mostrar detalle de un Cliente.
     *
     * Retorna toda la información del cliente con sus relaciones:
     * - País
     * - Usuario (con zona horaria)
     * - Moneda principal
     * - Otras monedas
     * - Juegos permitidos
     *
     * @param string $id ID hasheado con Hashids
     * @param CustomerService $service Servicio de lógica de negocio
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con los datos del cliente
     */
    public function show(string $id, CustomerService $service)
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

            $customer = $service->getById($realId);

            return response()->json([
                'success' => true,
                'data' => $customer
            ]);
        } catch (\Exception $e) {
            Log::error('CustomerController::show - Error:', [
                'hashed_id' => $id,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => "No se pudo cargar la información del Cliente: {$e->getMessage()}"
            ], 500);
        }
    }

    /**
     * Listar todos los Clientes.
     *
     * Retorna la vista con todos los clientes y sus relaciones básicas.
     *
     * @param CustomerService $service Servicio de lógica de negocio
     * @return \Illuminate\View\View|RedirectResponse Vista con los clientes
     */
    public function index(CustomerService $service)
    {
        return $this->handleService(function () use ($service) {
            $customers = $service->getAll();
            return view('customer.index', compact('customers'));
        }, "No se pudo cargar la lista de Clientes.");
    }
}
