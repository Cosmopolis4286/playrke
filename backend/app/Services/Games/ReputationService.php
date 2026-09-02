<?php

declare(strict_types=1);

namespace App\Services\Games;

use App\Domains\Marketplace\Escrow\Models\Escrow;
use App\Models\AntiCheat\AntiCheatFlag;
use App\Models\AntiCheat\UserSuspension;
use App\Models\GameParticipation;
use App\Models\User;
use App\Models\UserReputation;

/**
 * Calcula y cachea un score de reputación (0-100) por usuario, derivado
 * exclusivamente de señales que YA existen en el sistema — no introduce
 * ningún dato nuevo que el jugador no haya generado con su comportamiento:
 *
 * - Severidad acumulada de AntiCheatFlag (todo el historial, no solo la
 *   ventana de auto-suspensión que usa AntiCheatService — para reputación
 *   interesa el patrón completo, no solo lo reciente).
 * - Suspensión activa (penalización fuerte, no solo proporcional).
 * - Volumen de partidas jugadas sin incidentes (game_participations),
 *   como un bonus pequeño — más historial limpio, más confianza.
 * - Disputas de Marketplace perdidas (escrows.resolution contra el usuario),
 *   penalización pequeña — el marketplace nunca escribe UserReputation
 *   directamente, solo se consulta como señal más (ver
 *   MARKETPLACE_ARCHITECTURE_AUDIT.md sección 26).
 *
 * No modela "abandono": game_participations no distingue partidas
 * abandonadas de partidas nunca reportadas (GameCompletionService solo
 * registra al CERRAR la partida), así que esa señal no existe todavía. Ver
 * progreso/matchmaking-plan.md.
 */
final class ReputationService
{
    private const MAX_SCORE = 100;
    private const MIN_SCORE = 0;

    /** Cuántos puntos resta cada punto de severidad acumulada de anti-cheat. */
    private const SEVERITY_PENALTY_FACTOR = 2;

    /** Penalización fija si el usuario tiene una suspensión activa ahora mismo. */
    private const ACTIVE_SUSPENSION_PENALTY = 40;

    /** Bonus máximo alcanzable por volumen de partidas limpias. */
    private const MAX_VOLUME_BONUS = 10;

    /** Partidas necesarias para alcanzar el bonus máximo de volumen. */
    private const VOLUME_BONUS_THRESHOLD = 50;

    /** Penalización fija por cada disputa de marketplace perdida como comprador o vendedor. */
    private const LOST_DISPUTE_PENALTY = 5;

    public function recalculate(User $user): UserReputation
    {
        $score = self::MAX_SCORE;

        $severity = (int) AntiCheatFlag::forUser((string) $user->id)->sum('severity');
        $score -= $severity * self::SEVERITY_PENALTY_FACTOR;

        if (UserSuspension::where('user_id', $user->id)->whereNull('resolved_at')->exists()) {
            $score -= self::ACTIVE_SUSPENSION_PENALTY;
        }

        $gamesPlayed = GameParticipation::where('user_id', $user->id)->count();
        $volumeBonus = (int) min(self::MAX_VOLUME_BONUS, floor($gamesPlayed / self::VOLUME_BONUS_THRESHOLD * self::MAX_VOLUME_BONUS));
        $score += $volumeBonus;

        $score -= $this->lostMarketplaceDisputes($user) * self::LOST_DISPUTE_PENALTY;

        $score = (int) max(self::MIN_SCORE, min(self::MAX_SCORE, $score));

        return UserReputation::updateOrCreate(
            ['user_id' => $user->id],
            ['score' => $score, 'calculated_at' => now()],
        );
    }

    /**
     * Disputas de escrow resueltas en contra del usuario: como comprador si
     * SELLER_WINS, como vendedor si BUYER_WINS. CANCELLED no cuenta contra
     * nadie — es una salida neutral, no una disputa perdida.
     */
    private function lostMarketplaceDisputes(User $user): int
    {
        $lostAsBuyer = Escrow::where('buyer_id', $user->id)
            ->where('resolution', Escrow::RESOLUTION_SELLER_WINS)
            ->count();

        $lostAsSeller = Escrow::where('seller_id', $user->id)
            ->where('resolution', Escrow::RESOLUTION_BUYER_WINS)
            ->count();

        return $lostAsBuyer + $lostAsSeller;
    }

    /**
     * Devuelve el score cacheado sin recalcular. Si el usuario no tiene
     * registro todavía, lo crea con el score por defecto (no recalcula desde
     * cero para no penalizar el primer acceso con una query pesada).
     */
    public function getScore(User $user): int
    {
        $reputation = UserReputation::firstOrCreate(
            ['user_id' => $user->id],
            ['score' => UserReputation::DEFAULT_SCORE],
        );

        return $reputation->score;
    }
}
