<?php

namespace App\Http\Controllers\Games\Bingo\Room;

use App\Domains\Games\Bingo\DTOs\Room\Automatic\BingoRoomAutomaticCreateDTO;
use App\Domains\Games\Bingo\DTOs\Room\Automatic\BingoRoomAutomaticUpdateDTO;
use App\Domains\Games\Bingo\Models\Room\BingoRoomAutomatic;
use App\Domains\Games\Bingo\Services\Room\Automatic\AutoGenerateBingoRoomsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Games\Bingo\Room\Automatic\BingoRoomAutomaticRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de administración de las salas automaticas/regenerables del juego de Bingo.
 *
 * Responsabilidades:
 * - Orquestar requests HTTP
 * - Delegar lógica de negocio al servicio
 * - Unificar respuestas JSON
 *
 * NO contiene lógica de dominio ni mensajes de error.
 */
final class BingoRoomAutomaticController extends Controller
{
    /**
     * @param BingoRoomAutomaticService $service
     */
    public function __construct(
        private readonly \App\Domains\Games\Bingo\Services\Room\Automatic\BingoRoomAutomaticService $service,
        private readonly \App\Services\Security\UserContextService $userContext,
        private readonly AutoGenerateBingoRoomsService $autoGenerator,
    ) {
        $this->authorizeResource(BingoRoomAutomatic::class, 'room');
    }

    /**
     * Copiar una sala automatica de bingo (status INACTIVE).
     */
    public function copy(BingoRoomAutomatic $room) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->copy($room),
        );
    }

    /**
     * Eliminar una sala automatica (autogenerable) de bingo (soft delete).
     */
    public function delete(BingoRoomAutomatic $room) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->delete($room),
        );
    }

    /**
     * Eliminar una sala automatica (autogenerable) de bingo permanentemente (hard delete).
     */
    public function destroy(BingoRoomAutomatic $room) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->destroy($room),
        );
    }

    /**
     * Ejecuta el auto-generador de salas de bingo de forma manual.
     * Equivale a correr el Artisan command generate:bingo-rooms
     * pero restringido al creador autenticado.
     */
    public function executeJob() : JsonResponse
    {
        return $this->handleServiceJson(function() {
            $userId = $this->userContext->resolveUserId();

            $created = $this->autoGenerator->createBingoRooms([
                'id' => $userId,
            ]);

            return [
                'created' => $created,
                'message' => $created
                    ? 'Salas generadas correctamente.'
                    : 'No hay plantillas activas para el horario actual.',
            ];
        });
    }

    /**
     * Obtener lista de salas automaticas de bingo filtradas.
     */
    public function fetchList() : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => ['data' => $this->service->fetchList()],
        );
    }

    /**
     * Resumen de salas automaticas de bingo (admin y commerce).
     */
    public function index() : Response
    {
        return $this->handleServiceInertia(
            function() {
                $userId = $this->userContext->resolveUserId();
                $response = $this->service->context('summary', $userId);

                return Inertia::render('Games/Bingo/Room/Automatic/Summary', $response);
            },
            'No se pudo cargar la lista de salas de bingo.',
        );
    }

    /**
     * Mostrar detalle de una sala automatica de bingo.
     */
    public function show(BingoRoomAutomatic $room) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => [
                'data' => $this->service->show($room),
            ],
        );
    }

    /**
     * Crear una sala automatica (autogenerable) de bingo.
     */
    public function store(BingoRoomAutomaticRequest $request) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->create(
                $request->toDTO(BingoRoomAutomaticCreateDTO::class),
            ),
        );
    }

    /**
     * Refetch paginado/filtrado del listado de salas automáticas
     * (Room/Automatic/Summary), usado por la tabla en modo server-side
     * tras la carga inicial de la página.
     */
    public function summaryData(Request $request) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->summaryData(
                page: (int) $request->query('page', 1),
                perPage: (int) $request->query('per_page', 15),
                filters: array_filter([
                    'status' => $request->query('status'),
                    'room_type_id' => $request->query('room_type_id'),
                    'connection_type_id' => $request->query('connection_type_id'),
                    'prize_pool_type_id' => $request->query('prize_pool_type_id'),
                    'currency_id' => $request->query('currency_id'),
                ]),
            ),
        );
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) de una sala automatica de bingo.
     */
    public function toggleStatus(BingoRoomAutomatic $room) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->toggleStatus($room),
        );
    }

    /**
     * Actualizar una sala automatica (autogenerable) de bingo.
     */
    public function update(BingoRoomAutomaticRequest $request, BingoRoomAutomatic $room) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->update(
                $room,
                $request->toDTO(BingoRoomAutomaticUpdateDTO::class),
            ),
        );
    }
}
