<?php

namespace App\Http\Controllers\Games\Bingo\Room;

use App\Http\Controllers\Controller;

use App\DTOs\Games\Bingo\Room\Automatic\BingoRoomAutomaticCreateDTO;
use App\Services\Games\Bingo\Room\Automatic\AutoGenerateBingoRoomsService;
use App\DTOs\Games\Bingo\Room\Automatic\BingoRoomAutomaticUpdateDTO;
use App\Http\Requests\Games\Bingo\Room\Automatic\BingoRoomAutomaticRequest;
use App\Models\Games\Bingo\Room\BingoRoomAutomatic;
use Illuminate\Http\JsonResponse;
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
        private readonly \App\Services\Games\Bingo\Room\Automatic\BingoRoomAutomaticService $service,
        private readonly \App\Services\Security\UserContextService $userContext,
        private readonly AutoGenerateBingoRoomsService $autoGenerator,
    ) {
        $this->authorizeResource(BingoRoomAutomatic::class, 'room');
    }

    /**
     * Resumen de salas automaticas de bingo (admin y commerce).
     *
     * @return Response
     */
    public function index(): Response
    {
        return $this->handleServiceInertia(
            function () {
                $userId = $this->userContext->resolveUserId();
                $response = $this->service->context('summary', $userId);

                return Inertia::render('Games/Bingo/Room/Automatic/Summary', $response);
            },
            'No se pudo cargar la lista de salas de bingo.'
        );
    }

    /**
     * Crear una sala automatica (autogenerable) de bingo.
     *
     * @param BingoRoomAutomaticRequest $request
     * @return JsonResponse
     */
    public function store(BingoRoomAutomaticRequest $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->create(
                $request->toDTO(BingoRoomAutomaticCreateDTO::class)
            )
        );
    }

    /**
     * Obtener lista de salas automaticas de bingo filtradas.
     *
     * @return JsonResponse
     */
    public function fetchList(): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => ['data' => $this->service->fetchList()]
        );
    }

    /**
     * Mostrar detalle de una sala automatica de bingo.
     *
     * @param BingoRoomAutomatic $room
     * @return JsonResponse
     */
    public function show(BingoRoomAutomatic $room): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'data' => $this->service->show($room),
            ]
        );
    }

    /**
     * Actualizar una sala automatica (autogenerable) de bingo.
     *
     * @param BingoRoomAutomaticRequest $request
     * @param BingoRoomAutomatic $room
     * @return JsonResponse
     */
    public function update(BingoRoomAutomaticRequest $request, BingoRoomAutomatic $room): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->update(
                $room,
                $request->toDTO(BingoRoomAutomaticUpdateDTO::class)
            )
        );
    }

    /**
     * Eliminar una sala automatica (autogenerable) de bingo (soft delete).
     *
     * @param BingoRoomAutomatic $room
     * @return JsonResponse
     */
    public function delete(BingoRoomAutomatic $room): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->delete($room)
        );
    }

    /**
     * Eliminar una sala automatica (autogenerable) de bingo permanentemente (hard delete).
     *
     * @param BingoRoomAutomatic $room
     * @return JsonResponse
     */
    public function destroy(BingoRoomAutomatic $room): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->destroy($room)
        );
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) de una sala automatica de bingo.
     *
     * @param BingoRoomAutomatic $room
     * @return JsonResponse
     */
    public function toggleStatus(BingoRoomAutomatic $room): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->toggleStatus($room)
        );
    }

    /**
     * Copiar una sala automatica de bingo (status INACTIVE).
     *
     * @param BingoRoomAutomatic $room
     * @return JsonResponse
     */
    public function copy(BingoRoomAutomatic $room): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->copy($room)
        );
    }

    /**
     * Ejecuta el auto-generador de salas de bingo de forma manual.
     * Equivale a correr el Artisan command generate:bingo-rooms
     * pero restringido al creador autenticado.
     *
     * @return JsonResponse
     */
    public function executeJob(): JsonResponse
    {
        return $this->handleServiceJson(function () {
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
}
