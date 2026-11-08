<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Actions;

use App\Domains\Games\Trivia\Contracts\GameEngineInterface;
use App\Domains\Games\Trivia\Contracts\RoomServiceInterface;
use App\Domains\Games\Trivia\DTOs\GameStateDTO;
use App\Domains\Games\Trivia\Exceptions\Game\TriviaGameException;
use App\Domains\Games\Trivia\Exceptions\Room\TriviaRoomException;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Domains\Games\Trivia\Services\TriviaRoomRefundService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Action: Iniciar Juego.
 *
 * Soporta dos modos:
 *   - Manual (hostId = ID del usuario que hace clic) → valida permisos
 *   - Automático (hostId = null)                    → inicio programado por el sistema
 */
class StartGameAction
{
    public function __construct(
        private readonly GameEngineInterface $gameEngine,
        private readonly RoomServiceInterface $roomService,
        private readonly TriviaRoomRefundService $refundService,
    ) {
    }

    /**
     * @param string      $roomId    ID de la sala
     * @param string|null $hostId    ID del anfitrión. null = inicio automático (sin validar permisos)
     * @param string[]    $playerIds IDs de jugadores confirmados
     *
     * @throws TriviaRoomException
     */
    public function execute(string $roomId, ?string $hostId, array $playerIds) : GameStateDTO
    {
        // ── 1. Cargar sala desde BD (fuente de verdad) ────────────────────────
        $room = TriviaRoom::findOrFail($roomId);

        if ($room->status !== 'waiting') {
            throw TriviaRoomException::notInWaitingState($room->status);
        }

        // ── 2. Validar permisos del host (solo en inicio manual) ──────────────
        if ($hostId !== null && (string) $room->host_id !== $hostId) {
            throw TriviaRoomException::startForbidden();
        }

        // ── 3. Validar jugadores desde BD si playerIds está vacío o Redis expiró
        if (empty($playerIds)) {
            $playerIds = DB::connection('pgsql_trivia')
                ->table('trivia_participants')
                ->where('room_id', $roomId)
                ->whereNull('deleted_at')
                ->whereIn('status', ['confirmed', 'registered'])
                ->pluck('user_id')
                ->map(fn ($id) => (string) $id)
                ->all();
        }

        if (empty($playerIds)) {
            throw TriviaRoomException::noPlayersRegistered();
        }

        // ── 4. Iniciar juego y enviar primera pregunta ────────────────────────
        try {
            $gameState = $this->gameEngine->startGame($roomId, $playerIds);
        } catch (TriviaGameException $e) {
            // Sin preguntas suficientes, startGame() deja la sala en
            // 'cancelling': se reembolsa la entrada ya, con el motivo real.
            $room->refresh();
            if ($room->status === 'cancelling') {
                try {
                    $this->refundService->cancelWithRefund(
                        $room,
                        "La sala '{$room->title}' ({$room->code}) se canceló porque no había preguntas suficientes para jugar. Se te reembolsó tu entrada.",
                    );
                } catch (Throwable $refundError) {
                    // Queda en 'cancelling': trivia:cancel-stale-rooms reintenta.
                    Log::error('[StartGameAction] No se pudo completar el reembolso por falta de preguntas', [
                        'room_id' => $roomId,
                        'error' => $refundError->getMessage(),
                    ]);
                }
            }

            throw $e;
        }
        $this->gameEngine->sendNextQuestion($gameState->gameId);

        return $gameState;
    }
}
