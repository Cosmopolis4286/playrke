<?php

namespace App\Http\Controllers\Games\Bingo\Game;

use App\DTOs\Games\Bingo\Room\BingoRoomCreateDTO;
use App\DTOs\Games\Bingo\Room\BingoRoomPlayerBuyDTO;
use App\DTOs\Games\Bingo\Room\BingoRoomUpdateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Games\Bingo\Room\BingoRoomPlayerBuyRequest;
use App\Http\Requests\Games\Bingo\Room\BingoRoomRequest;
use Illuminate\Http\Request;
use App\Models\Games\Bingo\Room\BingoRoom;
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
        private readonly \App\Services\Games\Bingo\Card\Generate\BingoCardGenerateService $generateService,
    ) {
        $this->authorizeResource(BingoRoom::class, 'room');
    }

    /**
     * Resumen de salas de bingo (admin y commerce).
     *
     * @return Response
     */
    public function inGame($player, $room = '')
    {
        // Vista para el juego de la venta conjunta de todos los comercios
        $player = ExternalPlayers::getByLink($player, ['room', 'creator_user_id', 'link', 'currency']);

        if (!$player) abort(404); // Retorna un error 404 Not Found

        $creatorUser = $player->creator_user;
        $userCreator = User::getCreatorUser($creatorUser, false, true);

        if (!$player) abort(404); // Retorna un error 404 Not Found

        $creator = [
            'type' => $userCreator['type'],
            'id' => $userCreator['id'],
            'userId' => $creatorUser
        ];

        $viewData = self::getGameInfo($creator, [
            'playerLink' => $player->link,
            'room' => $player->room,
            'currency' => $player->currency
        ]);

        // Retorna a vista con la informacion
        return $this->handleViewData($viewData, $creator['type']);
    }
}
