<?php

declare(strict_types=1);

namespace App\Services\Games;

use App\Models\GameParticipation;
use App\Models\User;
use App\Services\User\UserLevelService;

/**
 * Puente cross-game Bingo → Trivia — ver
 * progreso/estrategia-producto-plan.md, Fase 8. Trivia era hasta ahora un
 * producto aislado, sin ningún incentivo que empujara a un jugador que
 * descubrió la plataforma por Bingo a probarlo. No cambia reglas de ningún
 * juego — solo un bonus de XP en el momento correcto (primer intento de
 * Trivia, habiendo jugado Bingo antes).
 */
final class CrossGameBridgeService
{
    public function __construct(
        private readonly UserLevelService $userLevelService,
    ) {
    }

    /**
     * Debe llamarse ANTES de registrar la GameParticipation de esta partida
     * de Trivia (GameCompletionService::reportParticipation() inserta esa
     * fila primero) — de lo contrario "primera vez en Trivia" siempre
     * contaría al menos 1 fila (la que se está registrando ahora mismo).
     *
     * @return bool true si corresponde otorgar el bonus (llamar
     *   awardFirstTriviaBonus() después de que la partida ya haya quedado
     *   registrada, para no otorgar XP de un evento que todavía podría
     *   fallar antes de persistirse).
     */
    public function qualifiesForFirstTriviaBonus(User $user, string $game): bool
    {
        if ($game !== GameParticipation::GAME_TRIVIA) {
            return false;
        }

        $alreadyPlayedTrivia = GameParticipation::where('user_id', $user->id)
            ->where('game', GameParticipation::GAME_TRIVIA)
            ->exists();

        if ($alreadyPlayedTrivia) {
            return false;
        }

        $hasPlayedBingo = GameParticipation::where('user_id', $user->id)
            ->where('game', GameParticipation::GAME_BINGO)
            ->exists();

        return $hasPlayedBingo;
    }

    public function awardFirstTriviaBonus(User $user): void
    {
        $xp = $this->userLevelService->calculateXpReward('first_trivia_after_bingo');
        $this->userLevelService->addXpToUser($user, $xp, 'Primera partida de Trivia (ya jugabas Bingo)');
    }
}
