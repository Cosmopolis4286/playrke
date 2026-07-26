<?php

namespace App\Http\Controllers\Games\Bingo\Game;

use App\Http\Controllers\Controller;
use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
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
final class BingoGameController extends Controller
{
    /**
     * @param BingoRoomService $service
     */
    public function __construct(
        private readonly \App\Services\Security\UserContextService $userContext,
        private readonly \App\Domains\Games\Bingo\Services\Game\BingoGameService $service,
        private readonly \App\Domains\Games\Bingo\Services\Game\BingoDrawService $drawService,
        private readonly \App\Domains\Games\Bingo\Services\Game\BingoWinnerDetectionService $winnerDetection,
        private readonly \App\Domains\Games\Bingo\Services\Card\Generate\BingoCardGenerateService $cardGenerateService,
        private readonly \App\Services\Roles\RoleUserService $roleUserService,
        private readonly \App\Domains\Games\Bingo\Services\Config\BingoConfigContextService $bingoConfigContext,
        private readonly \App\Domains\Games\Bingo\Services\Game\BingoPatternPositionResolver $patternResolver,
    ) {
        $this->authorizeResource(BingoRoom::class, 'room');
    }

    /**
     * Vista del lobby de bingo (pantalla previa al juego).
     *
     * @param BingoRoom $room
     * @return Response
     */
    public function lobby(BingoRoom $room): Response
    {
        return $this->handleServiceInertia(
            function () use ($room) {
                $userId = $this->userContext->resolveUserId();

                $room->load([
                    'status',
                    'config.roomType',
                    'config.connectionType',
                    'players' => fn ($q) => $q->where('user_id', (int) $userId)->limit(1),
                ]);

                $userCards = $room->players->first()?->cards_purchased ?? 0;

                $config = $room->config;

                // Resolver precio, moneda y tasa según el rol del usuario
                $role           = $this->roleUserService->getActiveUserRolePrefix($userId);
                $cardPriceUsd   = (float) ($config?->card_price_usd ?? 0);
                $cardPrice      = $cardPriceUsd;
                $currencySymbol = 'USD';
                $exchangeRate   = 1.0;

                if ($role === 'player') {
                    try {
                        $priceData      = $this->cardGenerateService->getPlayerCardPrice((string) $room->id, (int) $userId);
                        $cardPrice      = $priceData['card_price'];
                        $currencySymbol = $priceData['currency_symbol'];
                        $exchangeRate   = $priceData['exchange_rate'];
                    } catch (\Throwable) {
                        // fallback a USD si el jugador no tiene billetera configurada
                    }
                }

                $roomData = [
                    'id'                    => (string) $room->id,
                    'uuid_code'             => $room->uuid_code,
                    'status_code'           => $room->status_code,
                    'prize_pool'            => (float) $room->prize_pool,
                    'full_house_prize'      => $room->full_house_prize !== null ? (float) $room->full_house_prize : null,
                    'serial_prize'          => $room->serial_prize   !== null ? (float) $room->serial_prize   : null,
                    'total_players'         => (int) $room->total_players,
                    'total_cards'           => (int) $room->total_cards,
                    'close_room'            => $room->is_closed,
                    'currency_symbol'       => $currencySymbol,
                    'card_price'            => $cardPrice,
                    'card_price_usd'        => $cardPriceUsd,
                    'room_type_description' => $config?->roomType?->description ?? 'Pública',
                    'game_mode_description' => $config?->connectionType?->description ?? 'Mixto',
                    'unix_date'             => $config?->unix_date,
                    'date'                  => $config?->date?->format('Y-m-d'),
                    'time'                  => $config?->time,
                    'time_chip'             => $config?->time_chip,
                    'rounds'                => $config?->rounds,
                    'min_cards_to_start'    => $config?->min_cards_to_start,
                    'max_players'           => $config?->max_players,
                    'game_variant'          => $config?->game_variant,
                ];

                return Inertia::render('Games/Bingo/Lobby/Index', [
                    'room'             => $roomData,
                    'roomRouteKey'     => $room->getRouteKey(),
                    'userCards'        => $userCards,
                    'exchangeRate'     => $exchangeRate,
                    'bingoRules'       => $this->bingoConfigContext->getBingoRules((int) $userId),
                    'imageBingoRules'  => $this->bingoConfigContext->getImageBingoRules((int) $userId),
                ]);
            },
            'No se pudo cargar el lobby.'
        );
    }

    /**
     * Lista de jugadores de la sala (para el modal del lobby).
     */
    public function lobbyPlayers(BingoRoom $room): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewLobbyPlayers', $room);

        $players = $room->players()
            ->with('user:id,first_name,last_name')
            ->orderByDesc('cards_purchased')
            ->limit(50)
            ->get()
            ->map(fn ($ru) => [
                'user_id'         => (string) $ru->user_id,
                'name'            => $ru->user ? trim("{$ru->user->first_name} {$ru->user->last_name}") : '—',
                'cards_purchased' => (int) $ru->cards_purchased,
                'role'            => $ru->role,
            ]);

        return response()
            ->json(['players' => $players])
            ->header('Cache-Control', 'no-store');
    }

    /**
     * Vista del juego de bingo (jugador en sala).
     *
     * @param BingoRoom $room
     * @return Response
     */
    public function inGame(BingoRoom $room): Response
    {
        $this->authorize('viewGame', $room);

        return $this->handleServiceInertia(
            function () use ($room) {
                $userId = $this->userContext->resolveUserId();

                $response = $this->service->getInGameData($room, $userId);
                $response['isLocal'] = app()->environment('local');

                return Inertia::render('Games/Bingo/Game/Index', $response);
            },
            'No se pudo cargar la sala de juego.'
        );
    }

    /**
     * Estado actual del sorteo (API) — consumido por el cliente al
     * reconectar el WebSocket para resincronizar números cantados/ganadores
     * que pudo haber perdido mientras el socket estuvo caído.
     *
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function drawState(BingoRoom $room): JsonResponse
    {
        $this->authorize('viewGame', $room);

        return $this->handleServiceJson(
            fn () => $this->service->getDrawState($room)
        );
    }

    /**
     * Obtener cartones del usuario en una sala (API).
     *
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function getUserCards(BingoRoom $room): JsonResponse
    {
        $this->authorize('viewMyCards', $room);

        $userId = $this->userContext->resolveUserId();
        if (!$room->relationLoaded('status')) $room->load('status');
        $roomIsFinished = strtoupper($room->status?->code ?? '') === 'FINISHED';
        $cartons = $this->service->getUserCartons($room->id, $userId, $roomIsFinished);

        return response()->json([
            'success' => true,
            'data'    => $cartons,
            'message' => 'Cartones obtenidos exitosamente',
        ])->header('Cache-Control', 'no-store');
    }

    /**
     * Ver el detalle de un cartón GANADOR (usado por PlayersModal/GameEndModal
     * para mostrar la disposición del cartón que hizo BINGO).
     *
     * Restringido a cartones con premio registrado en esta sala — sin esto,
     * cualquier usuario con acceso a la sala podía consultar la disposición
     * de números de CUALQUIER cartón (ganador o no) adivinando su id.
     */
    public function viewCard(BingoRoom $room, string $card): JsonResponse
    {
        return $this->handleServiceJson(function () use ($room, $card) {
            $cardPrize = BingoCardPrize::where('room_id', (string) $room->id)
                ->where('winning_card_id', $card)
                ->with('winningCard')
                ->first();

            if (!$cardPrize || !$cardPrize->winningCard) {
                abort(404, 'Cartón no encontrado en esta sala.');
            }

            $bingoCard = $cardPrize->winningCard;

            // Carga lazy condicional: solo consulta round y pattern si hay round_id,
            // evitando 2 queries extra cuando el prize no tiene ronda asociada.
            $winningPositions = [];
            if ($cardPrize->round_id) {
                $cardPrize->load('round.bingoPattern');
            }
            $pattern = $cardPrize->round?->bingoPattern;
            if ($pattern) {
                $variant = $room->relationLoaded('config')
                    ? $room->config?->game_variant
                    : $room->load('config')->config?->game_variant;
                $winningPositions = $this->patternResolver->resolve(
                    $pattern,
                    $variant ?? \App\Domains\Games\Bingo\ValueObjects\BingoVariant::BINGO_75
                );
            }

            return [
                'success'          => true,
                'card'             => $bingoCard->toFrontendFormat(),
                'winningPositions' => $winningPositions,
            ];
        }, 'No se pudo obtener el cartón.');
    }

    /**
     * [DEV] Resetea unix_date de la sala a now+3min para pruebas locales.
     *
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function devResetStartTime(BingoRoom $room): JsonResponse
    {
        abort_if(!app()->environment('local'), 403, 'Solo disponible en entorno local.');

        return $this->handleServiceJson(
            fn() => $this->service->devResetStartTime($room)
        );
    }

    /**
     * [DEV] Inicia el sorteo de la sala (genera fichas, persiste y broadcast).
     * Solo disponible en entorno local.
     *
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function startDraw(BingoRoom $room): JsonResponse
    {
        // El docblock decía "solo disponible en entorno local" pero el código no
        // lo hacía cumplir — solo cambiaba el modo de ejecución (sync vs cola), sin
        // bloquear la acción fuera de local. Hoy es seguro porque la ruta ya está
        // gateada externamente (routes/modules/games/bingo.php), pero se agrega la
        // misma defensa en profundidad que devResetStartTime por si se expone la
        // ruta fuera de ese bloque en el futuro — este endpoint dispara sorteos
        // arbitrarios sin pasar por el cron/health-check normal.
        abort_if(!app()->environment('local'), 403, 'Solo disponible en entorno local.');

        return $this->handleServiceJson(
            function () use ($room) {
                $roomId = (int) $room->id;

                // En local no hay queue worker fiable — ejecutar sincrónico.
                // BingoDrawService usa CHIP_INTERVAL_DEV = 0 en local, así que
                // el sorteo completo termina en segundos sin bloquear mucho.
                @set_time_limit(120);
                \App\Domains\Games\Bingo\Jobs\StartBingoDrawJob::dispatchSync((string) $roomId);

                return [
                    'success' => true,
                    'room_id' => $roomId,
                    'message' => 'Sorteo iniciado. Las fichas se emitirán por WebSocket.',
                ];
            }
        );
    }

    /**
     * Verifica si el usuario tiene bingo en alguno de sus cartones.
     * Si hay bingo, declara al ganador y dispara evento WebSocket.
     *
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function checkBingo(BingoRoom $room): JsonResponse
    {
        $this->authorize('viewGame', $room);

        return $this->handleServiceJson(
            function () use ($room) {
                $userId = $this->userContext->resolveUserId();
                $winner = $this->winnerDetection->checkWinnerForUser($room, $userId);

                if ($winner) {
                    return [
                        'success' => true,
                        'hasBingo' => true,
                        'winner' => $winner,
                        'message' => '¡BINGO! Has completado tu cartón.',
                    ];
                }

                return [
                    'success' => true,
                    'hasBingo' => false,
                    'message' => 'No tienes bingo aún. Sigue atento.',
                ];
            },
            'Error al verificar bingo.'
        );
    }

}
