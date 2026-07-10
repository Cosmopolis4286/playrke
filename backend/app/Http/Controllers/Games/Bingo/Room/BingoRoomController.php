<?php

namespace App\Http\Controllers\Games\Bingo\Room;

use App\Domains\Games\Bingo\DTOs\Room\BingoRoomCreateDTO;
use App\Domains\Games\Bingo\DTOs\Room\BingoRoomPlayerBuyDTO;
use App\Domains\Games\Bingo\DTOs\Room\BingoRoomUpdateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Games\Bingo\Room\BingoRoomPlayerBuyRequest;
use App\Http\Requests\Games\Bingo\Room\BingoRoomRequest;
use Illuminate\Http\Request;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de administración del juego de Bingo.
 *
 * Responsabilidades:
 * - Orquestar requests HTTP
 * - Delegar lógica de negocio al servicio
 * - Unificar respuestas JSON
 *
 * NO contiene lógica de dominio ni mensajes de error.
 */
final class BingoRoomController extends Controller
{
    /**
     * @param BingoRoomService $service
     */
    public function __construct(
        private readonly \App\Services\Security\UserContextService $userContext,
        private readonly \App\Domains\Games\Bingo\Services\Room\BingoRoomService $service,
        private readonly \App\Domains\Games\Bingo\Services\Card\Generate\BingoCardGenerateService $generateService,
    ) {
        $this->authorizeResource(BingoRoom::class, 'room');
    }

    /**
     * Resumen de salas de bingo (admin y commerce).
     *
     * @return Response
     */
    public function index(): Response
    {
        return $this->handleServiceInertia(
            function () {
                $userId = $this->userContext->resolveUserId();
                $response = $this->service->context('summary', $userId);

                return Inertia::render('Games/Bingo/Room/Summary', $response);
            },
            'No se pudo cargar la lista de salas de bingo.'
        );
    }

    /**
     * Crear una sala de bingo.
     *
     * @param BingoRoomRequest $request
     * @return JsonResponse
     */
    public function store(BingoRoomRequest $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->create(
                $request->toDTO(BingoRoomCreateDTO::class)
            )
        );
    }

    /**
     * Obtener lista de salas de bingo filtradas por el contexto del usuario (admin o commerce).
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
     * Mostrar detalle de una sala de bingo.
     *
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function show(BingoRoom $room): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'data' => $this->service->show($room),
            ]
        );
    }

    /**
     * Actualizar una sala de bingo.
     *
     * @param BingoRoomRequest $request
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function update(BingoRoomRequest $request, BingoRoom $room): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->update(
                $room,
                $request->toDTO(BingoRoomUpdateDTO::class)
            )
        );
    }

    /**
     * Eliminar una sala de bingo (soft delete).
     *
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function delete(BingoRoom $room): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->delete($room)
        );
    }

    /**
     * Eliminar una sala de bingo permanentemente (hard delete).
     *
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function destroy(BingoRoom $room): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->destroy($room)
        );
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) de una sala de bingo.
     *
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function toggleStatus(BingoRoom $room): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->toggleStatus($room)
        );
    }

    /**
     * Cancelar una sala de bingo (cambiar estado a CANCELLED).
     * Solo se puede cancelar si la sala está ACTIVE y tiene jugadores.
     *
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function cancel(BingoRoom $room): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->cancel($room)
        );
    }

    /**
     * Activar manualmente el sorteo de una sala sin horario programado.
     * Solo puede ejecutarla el creador de la sala o un admin/superadmin.
     *
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function activateNow(BingoRoom $room): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->activateNow($room)
        );
    }

    /**
     * Programar la fecha/hora de inicio de una sala sin horario (start_mode = MANUAL),
     * convirtiéndola en una sala programada (start_mode = SCHEDULED).
     * Solo puede ejecutarla el creador de la sala o un admin/superadmin.
     *
     * @param Request $request Body con date (Y-m-d) y time (H:i)
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function scheduleStart(Request $request, BingoRoom $room): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
        ], [
            'date.required'        => 'La fecha es obligatoria.',
            'date.date'            => 'La fecha no es válida.',
            'date.after_or_equal'  => 'La fecha debe ser hoy o posterior.',
            'time.required'        => 'La hora es obligatoria.',
            'time.date_format'     => 'La hora debe tener formato HH:mm.',
        ]);

        return $this->handleServiceJson(
            fn() => $this->service->scheduleStart($room, $data['date'], $data['time'])
        );
    }

    /**
     * Copiar una sala de bingo (status INACTIVE).
     *
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function copy(BingoRoom $room): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->copy($room)
        );
    }

    /**
     * Listado visual de salas de bingo.
     *
     * @return Response
     */
    public function viewGameRooms(): Response
    {
        return $this->handleServiceInertia(
            function () {
                $userId = $this->userContext->resolveUserId();
                $response = $this->service->context('game-rooms', $userId);

                return Inertia::render('Games/Bingo/Room/GameRooms', $response);
            },
            'No se pudo cargar la lista de salas de bingo.'
        );
    }

    /**
     * Compra de cartones desde la vista game-rooms.
     *
     * Flujo exclusivo para commerce/seller — el jugador (player)
     * ya está registrado en el sistema. Solo se requiere país y cantidad.
     *
     * @param BingoRoom                $room    Sala decodificada por route model binding.
     * @param BingoRoomPlayerBuyRequest $request Body con num_ctns y country_id.
     * @return JsonResponse
     */
    public function buyFromGameRooms(BingoRoom $room, BingoRoomPlayerBuyRequest $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->generateService->buyFromGameRooms(
                BingoRoomPlayerBuyDTO::fromArray(
                    $request->validated(),
                    (string) $room->id,
                )
            )
        );
    }

    /**
     * Compra de cartones para jugadores (player role).
     *
     * Flujo simplificado para jugadores que ya tienen billetera configurada.
     * Acepta opcionalmente `custom_numbers` (matriz elegida manualmente por el
     * jugador) cuando num_ctns es exactamente 1 — se valida contra la variante
     * de la sala en BingoCardGenerateService::playerBuyFromGameRooms().
     *
     * @param BingoRoom $room    Sala decodificada por route model binding.
     * @param Request    $request Body con num_ctns y, opcionalmente, custom_numbers.
     * @return JsonResponse
     */
    public function playerBuyFromGameRooms(BingoRoom $room, Request $request): JsonResponse
    {
        $numCtns = (int) $request->input('num_ctns', 1);

        $request->validate([
            'num_ctns'         => 'required|integer|min:1|max:40',
            // Array de matrices, uno por cartón. Cada elemento es array<col,int[]>.
            'custom_numbers'   => 'sometimes|nullable|array',
            'custom_numbers.*' => 'array',
        ]);

        // Validar que el número de matrices coincida con num_ctns si se envían
        $customNumbers = $request->input('custom_numbers');
        if ($customNumbers !== null && count($customNumbers) !== $numCtns) {
            return response()->json([
                'message' => 'El número de matrices personalizadas debe coincidir con num_ctns.',
            ], 422);
        }

        return $this->handleServiceJson(
            fn() => $this->generateService->playerBuyFromGameRooms(
                $room->id,
                $numCtns,
                $customNumbers,
            )
        );
    }

    /**
     * Verificar el rol del usuario autenticado.
     *
     * @return JsonResponse
     */
    public function checkUserRole(): JsonResponse
    {
        $isPlayer = $this->userContext->userHasRole('player');

        return $this->handleServiceJson(
            fn() => [
                'is_player' => $isPlayer,
                'user_id' => $this->userContext->isAuthenticated() ? $this->userContext->resolveUserId() : null,
            ]
        );
    }

    /**
     * Obtener el precio del cartón para un jugador usando su billetera configurada.
     *
     * @param BingoRoom $room Sala de bingo (model binding por {room}).
     * @return JsonResponse  Respuesta con card_price, currency_symbol, exchange_rate, is_custom_price.
     */
    public function playerCardPrice(BingoRoom $room): JsonResponse
    {
        $userId = $this->userContext->resolveUserId();

        return $this->handleServiceJson(
            fn() => $this->generateService->getPlayerCardPrice($room->id, $userId)
        );
    }
}
