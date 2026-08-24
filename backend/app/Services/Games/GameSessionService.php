<?php

declare(strict_types=1);

namespace App\Services\Games;

use App\Models\GameSession;
use App\Models\User;

/**
 * Orquesta el ciclo de vida de una GameSession — ver progreso/game-engine-plan.md.
 *
 * Esqueleto de Fase 1: no lo llama ningún juego todavía (no existe consumidor
 * real hasta que se diseñe el juego de duración variable que lo necesite,
 * ej. Transportistas vs Cazadores). Mismo patrón que GameCompletionService/
 * WalletReservationService: un juego llama a este servicio explícitamente en
 * sus puntos de inicio/fin, sin eventos ni listeners automáticos (el proyecto
 * no tiene EventServiceProvider).
 */
final class GameSessionService
{
    /**
     * Crea una sesión en estado WAITING.
     */
    public function start(string $game, ?User $initiator = null, array $metadata = []): GameSession
    {
        return GameSession::create([
            'game'               => $game,
            'status'             => GameSession::STATUS_WAITING,
            'initiator_user_id'  => $initiator?->id,
            'started_at'         => now(),
            'metadata'           => $metadata,
        ]);
    }

    /**
     * Transiciona la sesión a RUNNING. Solo válido desde WAITING.
     */
    public function run(GameSession $session): GameSession
    {
        if ($session->status !== GameSession::STATUS_WAITING) {
            throw new \DomainException("No se puede iniciar una sesión en estado {$session->status}.");
        }

        $session->update(['status' => GameSession::STATUS_RUNNING]);

        return $session->fresh();
    }

    /**
     * Cierra la sesión como FINISHED. Solo válido desde WAITING o RUNNING.
     *
     * No otorga XP/logros/premios por sí mismo — eso sigue siendo
     * responsabilidad de GameCompletionService/GamePrizeService, llamados
     * por el propio juego con los datos de resultado que solo él conoce.
     */
    public function finish(GameSession $session): GameSession
    {
        if (! $session->isActive()) {
            throw new \DomainException("No se puede finalizar una sesión en estado {$session->status}.");
        }

        $session->update([
            'status'      => GameSession::STATUS_FINISHED,
            'finished_at' => now(),
        ]);

        return $session->fresh();
    }

    /**
     * Cancela la sesión. Solo válido desde WAITING o RUNNING.
     */
    public function cancel(GameSession $session): GameSession
    {
        if (! $session->isActive()) {
            throw new \DomainException("No se puede cancelar una sesión en estado {$session->status}.");
        }

        $session->update([
            'status'      => GameSession::STATUS_CANCELLED,
            'finished_at' => now(),
        ]);

        return $session->fresh();
    }
}
