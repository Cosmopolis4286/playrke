<?php

namespace App\Http\Controllers\Examples;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\Games\Bingo\BingoRoomRepositoryContract;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Ejemplo de controlador usando el patrón Repository
 *
 * ANTES: El controlador usaba métodos estáticos del modelo directamente
 * DESPUÉS: El controlador usa el repositorio a través de inyección de dependencias
 *
 * Beneficios:
 * - Separación de responsabilidades
 * - Facilita el testing (se puede mockear el repositorio)
 * - Código más limpio y mantenible
 * - Siguie principios SOLID
 */
class BingoRoomControllerExample extends Controller
{
    /**
     * Constructor con inyección de dependencias
     */
    public function __construct(
        private readonly BingoRoomRepositoryContract $bingoRoomRepository
    ) {}

    /**
     * Listar salas de bingo
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Obtener criterios de búsqueda desde la request
            $criteria = array_filter([
                'status_id' => $request->get('status_id'),
                'room_type' => $request->get('room_type'),
                'creator_id' => $request->get('creator_id'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
            ]);

            // Usar el repositorio para buscar salas
            if (!empty($criteria)) {
                $rooms = $this->bingoRoomRepository->searchRooms($criteria);
            } else {
                $rooms = $this->bingoRoomRepository->getRecentRoomsWithStats(20);
            }

            return response()->json([
                'success' => true,
                'data' => $rooms,
                'total' => $rooms->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las salas',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Mostrar una sala específica
     */
    public function show(string $id): JsonResponse
    {
        try {
            // ANTES: BingoRoom::getById($id)
            // DESPUÉS: Uso del repositorio
            $room = $this->bingoRoomRepository->findOrFail($id);

            // Obtener estadísticas de la sala
            $stats = $this->bingoRoomRepository->getRoomSalesStats($id);
            $commissions = $this->bingoRoomRepository->getRoomCommissions($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'room' => $room,
                    'stats' => $stats,
                    'commissions' => $commissions,
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sala no encontrada',
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la sala',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Buscar sala por código encriptado
     */
    public function showByEncrypted(string $encryptedCode): JsonResponse
    {
        try {
            // ANTES: BingoRoom::getByEncrypted($encryptedCode)
            // DESPUÉS: Uso del repositorio
            $room = $this->bingoRoomRepository->findByEncryptedCode($encryptedCode);

            if (!$room) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sala no encontrada',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $room,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la sala',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Crear nueva sala
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validar datos de entrada
            $validatedData = $request->validate([
                'room_type' => 'required|in:PV,PB',
                'creator_user_id' => 'required|string|exists:users,id',
                'publication_date' => 'nullable|date',
                'prize_pool' => 'required|numeric|min:0',
                // ... más validaciones según sea necesario
            ]);

            // Crear sala usando el repositorio
            $room = $this->bingoRoomRepository->create($validatedData);

            return response()->json([
                'success' => true,
                'data' => $room,
                'message' => 'Sala creada exitosamente',
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la sala',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Cerrar una sala
     */
    public function closeRoom(string $id): JsonResponse
    {
        try {
            // Verificar que la sala existe
            $room = $this->bingoRoomRepository->findOrFail($id);

            if ($room->is_closed) {
                return response()->json([
                    'success' => false,
                    'message' => 'La sala ya está cerrada',
                ], 400);
            }

            // Cerrar la sala usando el repositorio
            $closed = $this->bingoRoomRepository->closeRoom($id);

            if ($closed) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sala cerrada exitosamente',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo cerrar la sala',
                ], 500);
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sala no encontrada',
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar la sala',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Obtener salas por creador
     */
    public function getByCreator(string $creatorId): JsonResponse
    {
        try {
            // ANTES: BingoRoom::where('creator_user_id', $creatorId)->get()
            // DESPUÉS: Uso del repositorio
            $rooms = $this->bingoRoomRepository->findByCreator($creatorId);

            return response()->json([
                'success' => true,
                'data' => $rooms,
                'total' => $rooms->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las salas del creador',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Obtener salas con premios pendientes
     */
    public function getPendingPrizes(): JsonResponse
    {
        try {
            $rooms = $this->bingoRoomRepository->getRoomsWithPendingPrizes([
                'id',
                'encrypted_code',
                'room_type',
                'prize_pool',
                'created_at'
            ]);

            return response()->json([
                'success' => true,
                'data' => $rooms,
                'total' => $rooms->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener salas con premios pendientes',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Actualizar pool de premios
     */
    public function updatePrizePool(Request $request, string $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'prize_pool' => 'required|numeric|min:0',
            ]);

            // Verificar que la sala existe
            $this->bingoRoomRepository->findOrFail($id);

            // Actualizar pool de premios
            $updated = $this->bingoRoomRepository->updatePrizePool($id, $validatedData['prize_pool']);

            if ($updated) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pool de premios actualizado exitosamente',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo actualizar el pool de premios',
                ], 500);
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sala no encontrada',
            ], 404);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el pool de premios',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}