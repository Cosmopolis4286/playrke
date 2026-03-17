<?php

namespace App\Http\Controllers;

use App\Models\Roles\Player\External\ExternalPlayer;
use App\Models\Games\Bingo\Card\BingoCard;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Vinkla\Hashids\Facades\Hashids;

class ExternalPlayerController extends Controller
{

    /**
     * Decodificar un ID hasheado usando la misma configuración del trait
     */
    public static function decodeHashedId(string $hashedId): ?int
    {
        $decodedIds = Hashids::connection('main')->decode($hashedId);
        return empty($decodedIds) ? null : $decodedIds[0];
    }

    /**
     * Verificar si un jugador externo existe para una sala y juego específicos
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|max:20',
            'country_id' => 'required|string',
            'game_id' => 'required|string',
            'room_id' => 'required|string',
        ]);

        $phone = $request->input('phone');
        $countryId = $request->input('country_id');
        $gameId = $request->input('game_id');
        $roomId = $request->input('room_id');

        try {
            if($gameId === 'bingo'){
                $gameId = Game::getValueByName($gameId, 'id');
            }

            // Decodificar $roomId usando el trait approach
            $roomId = self::decodeHashedId($roomId);
            if ($roomId === null) {
                return response()->json([
                    'success' => false,
                    'exists' => false,
                    'player' => null,
                    'message' => 'ID de sala inválido'
                ]);
            }

            // Buscar jugador externo por teléfono, país, juego y sala
            $externalPlayer = ExternalPlayer::where('phone', $phone)
                ->where('country_id', $countryId)
                ->where('game_id', $gameId)
                ->where('room_id', $roomId)
                ->first();

            if ($externalPlayer) {
                // Obtener conteo de cartones del jugador externo
                $cardCount = BingoCard::where('room_user_id', $externalPlayer->room_user_id)->count();
                
                return response()->json([
                    'success' => true,
                    'exists' => true,
                    'player' => [
                        'first_name' => $externalPlayer->first_name,
                        'last_name' => $externalPlayer->last_name,
                        'phone' => $externalPlayer->phone,
                        'card_count' => $cardCount,
                    ],
                    'message' => 'Jugador encontrado'
                ]);
            }

            return response()->json([
                'success' => true,
                'exists' => false,
                'player' => null,
                'message' => 'Jugador no encontrado'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'exists' => false,
                'player' => null,
                'message' => 'Error al verificar jugador: ' . $e->getMessage()
            ], 500);
        }
    }
}
