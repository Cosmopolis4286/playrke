<?php

namespace App\Http\Controllers;

use App\Contracts\Roles\Player\External\ExternalPlayerRepositoryInterface;
use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Models\Game;
use App\Models\Roles\Player\External\ExternalPlayerParticipation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Vinkla\Hashids\Facades\Hashids;

class ExternalPlayerController extends Controller
{
    public function __construct(
        private readonly ExternalPlayerRepositoryInterface $externalPlayerRepository,
        private readonly \App\Services\Roles\Player\External\ExternalPlayerLinkService $externalPlayerLink,
    ) {}

    /**
     * Decodificar un ID hasheado usando la misma configuración del trait
     */
    public static function decodeHashedId(string $hashedId): ?int
    {
        $decodedIds = Hashids::connection('main')->decode($hashedId);
        return empty($decodedIds) ? null : $decodedIds[0];
    }

    /**
     * Verificar si un jugador externo existe para una sala y juego específicos.
     * Usado por el vendedor al generar cartones (ver
     * BingoCardGenerateFormPlayerSection.vue) para reconocer a un comprador
     * recurrente antes de crear un nuevo registro.
     */
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'country_id' => 'required|string',
            'game_id' => 'required|string',
            'room_id' => 'required|string',
        ]);

        $gameId = $validated['game_id'] === 'bingo'
            ? Game::getValueByName('bingo', 'id')
            : $validated['game_id'];

        $roomId = self::decodeHashedId($validated['room_id']);

        if ($roomId === null) {
            return response()->json([
                'success' => false,
                'exists'  => false,
                'player'  => null,
                'message' => 'ID de sala inválido',
            ]);
        }

        // Solo jugadores captados por la red comercial del vendedor: antes
        // cualquier usuario autenticado obtenía nombre, cartones y link de
        // acceso de un comprador ajeno con solo su teléfono y el id de una
        // sala (p. ej. una PUBLIC compartida con otro comercio).
        $externalPlayer = $this->externalPlayerRepository->findByPhoneAndRoom(
            $validated['phone'],
            $validated['country_id'],
            (string) $gameId,
            (string) $roomId,
            $this->externalPlayerLink->actorNetworkUserIds($request->user()?->getKey()),
        );

        if (!$externalPlayer) {
            return response()->json([
                'success' => true,
                'exists'  => false,
                'player'  => null,
                'message' => 'Jugador no encontrado',
            ]);
        }

        // Conteo por jugador externo INDIVIDUAL, no por BingoRoomUser
        // compartido — varios ExternalPlayer del mismo vendedor en la misma
        // sala comparten room_user_id, así que contar por esa columna
        // devolvía los cartones de TODOS los compradores del vendedor, no
        // solo los de este jugador (ver ExternalPlayerParticipation).
        $cardCount = count(ExternalPlayerParticipation::participableIdsFor($externalPlayer->id, BingoCard::class));

        return response()->json([
            'success' => true,
            'exists'  => true,
            'player'  => [
                'first_name' => $externalPlayer->first_name,
                'last_name'  => $externalPlayer->last_name,
                'phone'      => $externalPlayer->phone,
                'card_count' => $cardCount,
                'link'       => route('external-players.game', ['link' => $externalPlayer->link]),
            ],
            'message' => 'Jugador encontrado',
        ]);
    }
}
