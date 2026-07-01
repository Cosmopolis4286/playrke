<?php

namespace App\Http\Controllers\Games\Bingo\Game;

use App\Http\Controllers\Controller;
use App\Domains\Games\Bingo\Models\Card\BingoCard;
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
        private readonly \App\Services\Games\Bingo\Game\BingoGameService $service,
        private readonly \App\Services\Games\Bingo\Game\BingoDrawService $drawService,
        private readonly \App\Services\Games\Bingo\Game\BingoWinnerDetectionService $winnerDetection,
        private readonly \App\Services\Games\Bingo\Card\Generate\BingoCardGenerateService $cardGenerateService,
        private readonly \App\Services\Roles\RoleUserService $roleUserService,
        private readonly \App\Services\Games\Bingo\Config\BingoConfigContextService $bingoConfigContext,
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
                    'config.gameMode',
                ]);

                $roomUser = \App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser::where('room_id', (string) $room->id)
                    ->where('user_id', (string) $userId)
                    ->first();

                $userCards = $roomUser?->cards_purchased ?? 0;

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
                    'full_house_prize'      => (float) $room->full_house_prize,
                    'serial_prize'          => (float) $room->serial_prize,
                    'total_players'         => (int) $room->total_players,
                    'total_cards'           => (int) $room->total_cards,
                    'close_room'            => $room->is_closed,
                    'currency_symbol'       => $currencySymbol,
                    'card_price'            => $cardPrice,
                    'card_price_usd'        => $cardPriceUsd,
                    'room_type_description' => $config?->roomType?->description ?? 'Pública',
                    'game_mode_description' => $config?->gameMode?->description ?? 'Mixto',
                    'unix_date'             => $config?->unix_date,
                    'date'                  => $config?->date?->format('Y-m-d'),
                    'time'                  => $config?->time,
                    'time_chip'             => $config?->time_chip,
                    'rounds'                => $config?->rounds,
                    'in_live'               => (bool) $config?->in_live,
                    'min_cards_to_start'    => $config?->min_cards_to_start,
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
        $players = $room->players()
            ->with('user:id,name')
            ->orderByDesc('cards_purchased')
            ->get()
            ->map(fn ($ru) => [
                'user_id'         => (string) $ru->user_id,
                'name'            => $ru->user?->name ?? '—',
                'cards_purchased' => (int) $ru->cards_purchased,
                'role'            => $ru->role,
            ]);

        return response()->json(['players' => $players]);
    }

    /**
     * Vista del juego de bingo (jugador en sala).
     *
     * @param BingoRoom $room
     * @return Response
     */
    public function inGame(BingoRoom $room): Response
    {
        return $this->handleServiceInertia(
            function () use ($room) {
                $userId = $this->userContext->resolveUserId();

                \Log::info('[BingoGameController] inGame loaded', [
                    'room_id' => $room->id,
                    'user_id' => $userId,
                    'auth_check' => auth()->check(),
                    'auth_id' => auth()->id(),
                ]);

                $response = $this->service->getInGameData($room, $userId);
                $response['isLocal'] = app()->environment('local');

                \Log::info('[BingoGameController] inGame response', [
                    'cartons_count' => count($response['cartons'] ?? []),
                    'cartons_data' => $response['cartons'] ?? [],
                ]);

                return Inertia::render('Games/Bingo/Game/Index', $response);
            },
            'No se pudo cargar la sala de juego.'
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
        return $this->handleServiceJson(
            function () use ($room) {
                $userId = $this->userContext->resolveUserId();
                $cartons = $this->service->getUserCartons($room->id, $userId);

                return [
                    'success' => true,
                    'data' => $cartons,
                    'message' => 'Cartones obtenidos exitosamente'
                ];
            },
            'No se pudieron obtener los cartones del usuario.'
        );
    }

    public function viewCard(BingoRoom $room, string $card): JsonResponse
    {
        return $this->handleServiceJson(function () use ($room, $card) {
            $bingoCard = BingoCard::where('id', $card)
                ->where('room_id', (string) $room->id)
                ->first();

            if (!$bingoCard) {
                abort(404, 'Cartón no encontrado en esta sala.');
            }

            return [
                'success' => true,
                'card'    => $bingoCard->toFrontendFormat(),
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
        return $this->handleServiceJson(
            function () use ($room) {
                $roomId = (int) $room->id;

                // El sorteo completo (~75 fichas) tarda varios minutos — se despacha
                // a la cola 'bingo-draw' para no bloquear el worker HTTP (ver StartBingoDrawJob).
                \App\Jobs\Games\Bingo\StartBingoDrawJob::dispatch((string) $roomId)->onQueue('bingo-draw');

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

    /**
     * Vista legacy del juego de bingo (compatibilidad).
     *
     * @param string $player
     * @param string $room
     * @return Response
     */
    public function legacyInGame(string $player, string $room = ''): Response
    {
        return $this->handleServiceInertia(
            function () use ($player, $room) {
                $response = $this->service->getLegacyInGameData($player, $room);

                return Inertia::render('Games/Bingo/Game/Index', $response);
            },
            'No se pudo cargar la sala de juego.'
        );
    }
}
