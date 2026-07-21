<?php

declare(strict_types=1);

namespace App\Services\Games;

use App\Models\GameParticipation;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\User\UserLevelService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Punto único donde cualquier juego reporta "un jugador terminó una partida".
 *
 * Registra la participación (game_participations), otorga el XP correspondiente
 * y evalúa el desbloqueo de logros — sin tocar la lógica de pago de premios de
 * cada juego (eso sigue viviendo en GamePrizeService/WalletService).
 *
 * Bingo y Trivia deben llamar a este servicio explícitamente en su punto de
 * cierre de partida (ver progreso/xp-logros-plan.md, Fase 3 y 4) en vez de
 * duplicar esta lógica o depender de eventos de broadcast.
 */
final class GameCompletionService
{
    public function __construct(
        private readonly UserLevelService $userLevelService,
        private readonly AchievementService $achievementService,
    ) {}

    /**
     * Registra el resultado de una partida para un jugador y otorga sus recompensas.
     *
     * @param  User    $user     Jugador que participó
     * @param  string  $game     GameParticipation::GAME_BINGO | GAME_TRIVIA
     * @param  bool    $won      Si el jugador ganó esta partida
     * @param  int     $xp       XP a otorgar por esta participación (0 = solo registrar)
     * @param  string|null  $roomId  ID de la sala/room, para trazabilidad
     * @param  array   $customContext  Datos extra para logros tipo 'custom' que un
     *                                 juego específico pueda determinar (ej. Bingo
     *                                 informando que se ganó con el último número
     *                                 llamado) — ver Achievement::checkCustomRequirement().
     */
    public function reportParticipation(
        User $user,
        string $game,
        bool $won,
        int $xp,
        ?string $roomId = null,
        array $customContext = [],
    ): void {
        DB::transaction(function () use ($user, $game, $won, $xp, $roomId) {
            GameParticipation::create([
                'user_id'    => $user->id,
                'game'       => $game,
                'room_id'    => $roomId,
                'won'        => $won,
                'xp_awarded' => $xp,
            ]);

            if ($xp > 0) {
                $reason = $won ? "Victoria en {$game}" : "Partida jugada en {$game}";
                $this->userLevelService->addXpToUser($user, $xp, $reason);
            }
        });

        try {
            $this->achievementService->checkAndUnlockAchievements($user->fresh(), array_merge([
                'game' => $game,
                'won'  => $won,
                'at'   => now(),
            ], $customContext));
        } catch (\Throwable $e) {
            // El resultado de la partida y el XP ya quedaron guardados; que la
            // evaluación de logros falle no debe revertir eso ni tumbar la
            // respuesta del juego.
            Log::warning('GameCompletionService: fallo al evaluar logros.', [
                'user_id' => $user->id,
                'game'    => $game,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
