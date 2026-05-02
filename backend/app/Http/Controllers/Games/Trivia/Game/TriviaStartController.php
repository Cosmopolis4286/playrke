<?php

declare(strict_types=1);

namespace App\Http\Controllers\Games\Trivia\Game;

use App\Domains\Games\Trivia\Actions\StartGameAction;
use App\Domains\Games\Trivia\Services\RoomService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * POST /trivia-game/room/{id}/start
 *
 * Solo el host (o admin) puede iniciar la partida.
 * Carga los participantes registrados, delega a StartGameAction
 * y devuelve el game_id para que el frontend redirija a /trivia/play/{gameId}.
 */
final class TriviaStartController extends Controller
{
    public function __invoke(Request $request, string $id): JsonResponse
    {
        $user        = Auth::user();
        $userId      = (string) $user->id;
        $startAction = app(StartGameAction::class);
        $roomService = app(RoomService::class);

        $room = \App\Domains\Games\Trivia\Models\TriviaRoom::findOrFail($id);
        $roomId = (string) $room->id;

        // ── Autorización: solo el host o admin/superadmin ──────────────────
        $isAdmin = $user->hasRole(['admin', 'superadmin']);
        if ((string) $room->host_id !== $userId && ! $isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Solo el anfitrión puede iniciar la partida.',
            ], 403);
        }

        // ── Estado: solo se puede iniciar desde 'waiting' ──────────────────
        if ($room->status !== 'waiting') {
            return response()->json([
                'success' => false,
                'message' => 'La sala no está en espera. Estado actual: ' . $room->status,
            ], 422);
        }

        // ── Cargar IDs de participantes confirmados ────────────────────────
        $playerIds = DB::connection('pgsql_trivia')
            ->table('trivia_participants')
            ->where('room_id', $roomId)
            ->whereNull('deleted_at')
            ->whereIn('status', ['confirmed', 'registered'])
            ->pluck('user_id')
            ->map(fn($id) => (string) $id)
            ->all();

        if (empty($playerIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No hay jugadores registrados en la sala.',
            ], 422);
        }

        try {
            $gameState = $startAction->execute($roomId, $userId, $playerIds);

            return response()->json([
                'success' => true,
                'game_id' => $gameState->gameId,
                'message' => 'Partida iniciada. Redirigiendo a los jugadores...',
            ]);

        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Throwable $e) {
            Log::error('[TriviaStartController] ' . $e->getMessage(), ['room_id' => $roomId]);

            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar la partida. Intenta de nuevo.',
            ], 500);
        }
    }
}
