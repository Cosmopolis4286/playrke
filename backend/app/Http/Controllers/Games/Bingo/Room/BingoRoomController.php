<?php

namespace App\Http\Controllers\Games\Bingo\Room;

use App\Domains\Games\Bingo\DTOs\Room\BingoRoomCreateDTO;
use App\Domains\Games\Bingo\DTOs\Room\BingoRoomUpdateDTO;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomAudit;
use App\Domains\Games\Bingo\Services\Room\BingoRoomAuditLogger;
use App\Http\Controllers\Controller;
use App\Http\Requests\Games\Bingo\Room\BingoRoomRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
     * Activar manualmente el sorteo de una sala sin horario programado.
     * Solo puede ejecutarla el creador de la sala o un admin/superadmin.
     */
    public function activateNow(BingoRoom $room) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->activateNow($room),
        );
    }

    /**
     * Cancelar una sala de bingo (cambiar estado a CANCELLED).
     * Solo se puede cancelar si la sala está ACTIVE y tiene jugadores.
     */
    public function cancel(BingoRoom $room) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->cancel($room),
        );
    }

    /**
     * Verificar el rol del usuario autenticado.
     */
    public function checkUserRole() : JsonResponse
    {
        $isPlayer = $this->userContext->userHasRole('player');

        return $this->handleServiceJson(
            fn () => [
                'is_player' => $isPlayer,
                'user_id' => $this->userContext->isAuthenticated() ? $this->userContext->resolveUserId() : null,
            ],
        );
    }

    /**
     * Copiar una sala de bingo (status INACTIVE).
     */
    public function copy(BingoRoom $room) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->copy($room),
        );
    }

    /**
     * Eliminar una sala de bingo (soft delete).
     */
    public function delete(BingoRoom $room) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->delete($room),
        );
    }

    /**
     * Eliminar una sala de bingo permanentemente (hard delete).
     */
    public function destroy(BingoRoom $room) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->destroy($room),
        );
    }

    /**
     * GET /bingo-rooms/export?search=&status=&...&date_from=&date_to=
     * Export CSV del listado de salas filtrado completo (no solo la página
     * visible) — mismo patrón que WalletMovementController::export().
     * Incluye última activación/desactivación manual (toggleStatus) y quién
     * la hizo, resuelto en batch para no incurrir en N+1 por chunk.
     */
    public function export(Request $request) : StreamedResponse
    {
        $this->authorize('viewAny', BingoRoom::class);

        $filters = array_filter([
            'search' => $request->query('search'),
            'status' => $request->query('status'),
            'room_type_id' => $request->query('room_type_id'),
            'connection_type_id' => $request->query('connection_type_id'),
            'prize_pool_type_id' => $request->query('prize_pool_type_id'),
            'from_house' => $request->query('from_house'),
            'currency_id' => $request->query('currency_id'),
            'commerce_id' => $request->query('commerce_id'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ]);

        $query = $this->service->exportQuery($filters);

        return response()->streamDownload(function() use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID', 'Código', 'Estado', 'Tipo', 'Modo', 'Tipo Bote', 'Creador', 'Jugadores', 'Cartones',
                'Premio', 'Creada', 'Última activación', 'Activada por', 'Última desactivación', 'Desactivada por',
            ]);

            $query->chunk(500, function($rooms) use ($handle) {
                $statusEvents = $this->resolveStatusEventsForExport($rooms->pluck('id')->all());

                foreach ($rooms as $room) {
                    $activated = $statusEvents[(string) $room->id]['ACTIVE'] ?? null;
                    $deactivated = $statusEvents[(string) $room->id]['INACTIVE'] ?? null;

                    fputcsv($handle, [
                        $room->id,
                        $room->uuid_code,
                        $room->status?->code,
                        $room->config?->roomType?->label,
                        $room->config?->connectionType?->label,
                        $room->config?->prizePoolType?->label,
                        $room->creator?->username,
                        $room->total_players,
                        $room->total_cards,
                        $room->prize_pool,
                        $room->created_at?->format('Y-m-d H:i:s'),
                        $activated?->created_at?->format('Y-m-d H:i:s'),
                        $activated?->user?->username,
                        $deactivated?->created_at?->format('Y-m-d H:i:s'),
                        $deactivated?->user?->username,
                    ]);
                }
            });

            fclose($handle);
        }, 'salas-bingo-' . now()->format('Y-m-d-His') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Obtener lista de salas de bingo filtradas por el contexto del usuario (admin o commerce).
     */
    public function fetchList() : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => ['data' => $this->service->fetchList()],
        );
    }

    /**
     * Refetch paginado/filtrado del listado de salas de juego (game-rooms),
     * usado por los filtros de fecha/variante del botón de filtros del
     * listado de jugador tras la carga inicial vía Inertia.
     */
    public function filterData(Request $request) : JsonResponse
    {
        $data = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'variant' => ['nullable', 'string', \Illuminate\Validation\Rule::in(['BINGO_75', 'BINGO_90', 'BINGO_80', 'BINGO_30'])],
        ]);

        return $this->handleServiceJson(
            fn () => $this->service->gameRoomsData(
                page: (int) $request->query('page', 1),
                perPage: (int) $request->query('per_page', 24),
                filters: array_filter([
                    'date_from' => $data['date_from'] ?? null,
                    'date_to' => $data['date_to'] ?? null,
                    'variant' => $data['variant'] ?? null,
                ]),
            ),
        );
    }

    /**
     * Resumen de salas de bingo (admin y commerce).
     */
    public function index() : Response
    {
        return $this->handleServiceInertia(
            function() {
                $userId = $this->userContext->resolveUserId();
                $response = $this->service->context('summary', $userId);

                return Inertia::render('Games/Bingo/Room/Summary', $response);
            },
            'No se pudo cargar la lista de salas de bingo.',
        );
    }

    /**
     * Resolver y verificar una sala privada a partir de su código de acceso,
     * sin conocer de antemano su ID ("Tengo un código" en el listado de
     * salas de juego). A diferencia de verifyAccess(), no recibe {room} vía
     * route model binding — la sala es desconocida hasta resolver el código.
     *
     * @param Request $request body con access_code y password
     */
    public function joinByCode(Request $request) : JsonResponse
    {
        $data = $request->validate([
            'access_code' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string'],
        ]);

        return $this->handleServiceJson(
            fn () => $this->service->joinRoomByCode($data['access_code'], $data['password']),
        );
    }

    /**
     * Compra de cartones para jugadores (player role).
     *
     * Flujo simplificado para jugadores que ya tienen billetera configurada.
     * Acepta opcionalmente `custom_numbers` (matrices elegidas manualmente por
     * el jugador) — una compra puede ser mixta: `custom_numbers` puede traer
     * menos elementos que `card_count` (el resto se genera al azar a precio
     * base). Cada elemento se valida contra la variante de la sala en
     * BingoCardGenerateService::playerBuyFromGameRooms(), que también aplica
     * el límite de cartones personalizables cuando la sala usa
     * custom_card_mode = 'every_x'.
     *
     * @param BingoRoom $room    sala decodificada por route model binding
     * @param Request   $request body con card_count y, opcionalmente, custom_numbers
     */
    public function playerBuyFromGameRooms(BingoRoom $room, Request $request) : JsonResponse
    {
        $cardCount = (int) $request->input('card_count', 1);

        $request->validate([
            'card_count' => 'required|integer|min:1|max:40',
            // Array de matrices personalizadas (0..card_count elementos). Cada
            // elemento es array<col,int[]>. Los cartones no cubiertos por este
            // array se generan al azar a precio base.
            'custom_numbers' => 'sometimes|nullable|array|max:40',
            'custom_numbers.*' => 'array',
        ]);

        $customNumbers = $request->input('custom_numbers');
        if ($customNumbers !== null && count($customNumbers) > $cardCount) {
            return response()->json([
                'message' => 'El número de matrices personalizadas no puede superar card_count.',
            ], 422);
        }

        return $this->handleServiceJson(
            fn () => $this->generateService->playerBuyFromGameRooms(
                $room->id,
                $cardCount,
                $customNumbers,
            ),
        );
    }

    /**
     * Obtener el precio del cartón para un jugador usando su billetera configurada.
     *
     * @param BingoRoom $room sala de bingo (model binding por {room})
     *
     * @return JsonResponse respuesta con card_price, currency_symbol, exchange_rate, is_custom_price
     */
    public function playerCardPrice(BingoRoom $room) : JsonResponse
    {
        $userId = $this->userContext->resolveUserId();

        return $this->handleServiceJson(
            fn () => $this->generateService->getPlayerCardPrice($room->id, $userId),
        );
    }

    /**
     * Programar la fecha/hora de inicio de una sala sin horario (start_mode = MANUAL),
     * convirtiéndola en una sala programada (start_mode = SCHEDULED).
     * Solo puede ejecutarla el creador de la sala o un admin/superadmin.
     *
     * @param Request $request Body con date (Y-m-d) y time (H:i)
     */
    public function scheduleStart(Request $request, BingoRoom $room) : JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
        ], [
            'date.required' => 'La fecha es obligatoria.',
            'date.date' => 'La fecha no es válida.',
            'date.after_or_equal' => 'La fecha debe ser hoy o posterior.',
            'time.required' => 'La hora es obligatoria.',
            'time.date_format' => 'La hora debe tener formato HH:mm.',
        ]);

        return $this->handleServiceJson(
            fn () => $this->service->scheduleStart($room, $data['date'], $data['time']),
        );
    }

    /**
     * Mostrar detalle de una sala de bingo.
     */
    public function show(BingoRoom $room) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => [
                'data' => $this->service->show($room),
            ],
        );
    }

    /**
     * Crear una sala de bingo.
     */
    public function store(BingoRoomRequest $request) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->create(
                $request->toDTO(BingoRoomCreateDTO::class),
            ),
        );
    }

    /**
     * Refetch paginado/filtrado del listado de salas (Room/Summary), usado
     * por la tabla en modo server-side tras la carga inicial de la página.
     */
    public function summaryData(Request $request) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->summaryData(
                page: (int) $request->query('page', 1),
                perPage: (int) $request->query('per_page', 15),
                filters: array_filter([
                    'search' => $request->query('search'),
                    'status' => $request->query('status'),
                    'room_type_id' => $request->query('room_type_id'),
                    'connection_type_id' => $request->query('connection_type_id'),
                    'prize_pool_type_id' => $request->query('prize_pool_type_id'),
                    'from_house' => $request->query('from_house'),
                    'currency_id' => $request->query('currency_id'),
                    // Solo admin/superadmin lo usan (ver BingoRoomContextService::
                    // resolveSummaryGameRooms()) — un commerce/customer ya ve
                    // únicamente sus propias salas, este filtro no le aporta nada.
                    'commerce_id' => $request->query('commerce_id'),
                    'date_from' => $request->query('date_from'),
                    'date_to' => $request->query('date_to'),
                ]),
            ),
        );
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) de una sala de bingo.
     */
    public function toggleStatus(BingoRoom $room) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->toggleStatus($room),
        );
    }

    /**
     * Top 10 compradores de la sala por cantidad de cartones — "Ver Top de
     * Compras" en la card del lobby de salas.
     *
     * @param BingoRoom $room sala de bingo (model binding por {room})
     */
    public function topBuyers(BingoRoom $room) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => ['buyers' => $this->service->topBuyers((string) $room->id)],
        );
    }

    /**
     * Actualizar una sala de bingo.
     */
    public function update(BingoRoomRequest $request, BingoRoom $room) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->update(
                $room,
                $request->toDTO(BingoRoomUpdateDTO::class),
            ),
        );
    }

    /**
     * Verificar código/contraseña de acceso a una sala privada.
     *
     * Solo aplica cuando roomType = PRIVATE; para cualquier otro tipo responde
     * éxito directo (no hay nada que verificar). Si es válido, marca la sala
     * como verificada en la sesión del usuario para no repetir el prompt.
     *
     * @param Request   $request body con access_code y password
     * @param BingoRoom $room    sala decodificada por route model binding
     */
    public function verifyAccess(Request $request, BingoRoom $room) : JsonResponse
    {
        $data = $request->validate([
            'access_code' => ['nullable', 'string'],
            'password' => ['nullable', 'string'],
        ]);

        return $this->handleServiceJson(
            fn () => $this->service->verifyRoomAccess(
                $room,
                $data['access_code'] ?? '',
                $data['password'] ?? '',
            ),
        );
    }

    /**
     * Listado visual de salas de bingo.
     */
    public function viewGameRooms() : Response
    {
        return $this->handleServiceInertia(
            function() {
                $userId = $this->userContext->resolveUserId();
                $response = $this->service->context('game-rooms', $userId);

                return Inertia::render('Games/Bingo/Room/GameRooms', $response);
            },
            'No se pudo cargar la lista de salas de bingo.',
        );
    }

    /**
     * Anular un sorteo ya iniciado (trabado o corrupto) y reembolsar los
     * cartones. Solo superadmin; ver BingoRoomCrudService::voidStartedDraw().
     */
    public function voidDraw(\Illuminate\Http\Request $request, BingoRoom $room) : JsonResponse
    {
        abort_unless($request->user()?->hasRole('superadmin'), 403, 'Solo un superadmin puede anular un sorteo.');
        $validated = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:255']]);

        return $this->handleServiceJson(
            fn () => ['data' => $this->service->voidStartedDraw($room, trim($validated['reason']))],
        );
    }

    /**
     * Última activación y última desactivación manual (toggleStatus) de cada
     * sala en $roomIds, resuelto en una sola query agrupada — evita N+1 por
     * fila del chunk del export. Mismo evento/origen que BingoRoomResource::
     * resolveLastStatusChange(), pero en batch para múltiples salas a la vez.
     *
     * @param array<int, string|int> $roomIds
     *
     * @return array<string, array{ACTIVE?: BingoRoomAudit, INACTIVE?: BingoRoomAudit}>
     */
    private function resolveStatusEventsForExport(array $roomIds) : array
    {
        if (empty($roomIds)) {
            return [];
        }

        $audits = BingoRoomAudit::whereIn('room_id', $roomIds)
            ->where('event', BingoRoomAuditLogger::EVENT_STATUS_CHANGED)
            ->with('user:id,username')
            ->orderByDesc('id')
            ->get();

        $result = [];
        foreach ($audits as $audit) {
            $roomId = (string) $audit->room_id;
            $newStatus = $audit->metadata['new_status'] ?? null;

            if (! $newStatus || isset($result[$roomId][$newStatus])) {
                continue;
            }

            $result[$roomId][$newStatus] = $audit;
        }

        return $result;
    }
}
