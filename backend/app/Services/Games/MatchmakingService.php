<?php

declare(strict_types=1);

namespace App\Services\Games;

use App\Models\GameSession;
use App\Models\GameSessionParticipant;
use App\Models\User;
use DomainException;
use Illuminate\Support\Collection;

/**
 * Matchmaking mínimo pero robusto sobre GameSession — ver
 * progreso/matchmaking-plan.md. Filtra sesiones WAITING candidatas por
 * entrada/moneda/nivel/reputación/cupo con queries SQL (no filtrado en PHP
 * sobre metadata json), y gestiona el join/leave del jugador vía
 * GameSessionParticipant.
 *
 * NO decide reglas de juego (cuándo una sesión pasa a RUNNING, cuántos
 * jugadores hacen falta para empezar, etc.) — eso sigue siendo
 * responsabilidad de cada juego. Este servicio solo resuelve "¿qué sesión
 * WAITING le ofrezco a este jugador?" y "¿tiene permitido entrar?".
 */
final class MatchmakingService
{
    public function __construct(
        private readonly ReputationService $reputationService,
    ) {
    }

    /**
     * Criterios soportados (todos opcionales):
     * - min_entry_fee / max_entry_fee (float)
     * - currency_id (string)
     * - limit (int, default 10) — cuántas candidatas devolver
     *
     * Solo devuelve sesiones para las que el jugador CALIFICA (nivel,
     * reputación, cupo) — no basta con que coincidan los criterios de
     * búsqueda, el jugador también debe poder unirse.
     *
     * @return Collection<int, GameSession>
     */
    public function findCandidateSessions(string $game, User $user, array $criteria = []) : Collection
    {
        $query = GameSession::query()
            ->forGame($game)
            ->where('status', GameSession::STATUS_WAITING);

        if (isset($criteria['min_entry_fee'])) {
            $query->where(function($q) use ($criteria) {
                $q->whereNull('entry_fee')->orWhere('entry_fee', '>=', $criteria['min_entry_fee']);
            });
        }

        if (isset($criteria['max_entry_fee'])) {
            $query->where(function($q) use ($criteria) {
                $q->whereNull('entry_fee')->orWhere('entry_fee', '<=', $criteria['max_entry_fee']);
            });
        }

        if (isset($criteria['currency_id'])) {
            $query->where(function($q) use ($criteria) {
                $q->whereNull('currency_id')->orWhere('currency_id', $criteria['currency_id']);
            });
        }

        $limit = (int) ($criteria['limit'] ?? 10);

        return $query->orderBy('created_at')
            ->limit($limit * 3) // margen: algunas se descartan por elegibilidad tras traerlas
            ->get()
            ->filter(fn (GameSession $session) => $this->isEligible($session, $user))
            ->values()
            ->take($limit);
    }

    /**
     * ¿Puede $user unirse a $session ahora mismo? Valida nivel mínimo,
     * cupo disponible y que no esté ya participando. La reputación mínima
     * se lee de session.metadata.min_reputation (no tiene columna propia —
     * a diferencia de min_level/max_participants, es un criterio que hoy
     * ningún consumidor real pidió con columna indexable; se agrega si hace
     * falta filtrar por rango en SQL más adelante).
     */
    public function isEligible(GameSession $session, User $user) : bool
    {
        if ($session->status !== GameSession::STATUS_WAITING) {
            return false;
        }

        if ($session->min_level !== null && $user->getCurrentLevel() < $session->min_level) {
            return false;
        }

        $minReputation = $session->metadata['min_reputation'] ?? null;
        if ($minReputation !== null && $this->reputationService->getScore($user) < $minReputation) {
            return false;
        }

        if (! $session->hasAvailableSlot()) {
            return false;
        }

        return ! ($session->participants()->where('user_id', $user->id)->active()->exists());
    }

    /**
     * Une al jugador a la sesión. Lanza DomainException si no es elegible —
     * llamar isEligible() antes si se quiere evitar la excepción (ej. para
     * filtrar una lista sin lanzar por cada una).
     */
    public function join(GameSession $session, User $user) : GameSessionParticipant
    {
        if (! $this->isEligible($session, $user)) {
            throw new DomainException('El jugador no cumple los requisitos para unirse a esta sesión.');
        }

        return GameSessionParticipant::create([
            'game_session_id' => $session->id,
            'user_id' => $user->id,
            'status' => GameSessionParticipant::STATUS_JOINED,
            'joined_at' => now(),
        ]);
    }

    /**
     * Marca al jugador como LEFT — no borra la fila (conserva el historial
     * de quién estuvo en la sesión, incluso si salió antes del cierre).
     */
    public function leave(GameSession $session, User $user) : void
    {
        $participant = $session->participants()
            ->where('user_id', $user->id)
            ->active()
            ->first();

        if (! $participant) {
            return;
        }

        $participant->update([
            'status' => GameSessionParticipant::STATUS_LEFT,
            'left_at' => now(),
        ]);
    }
}
