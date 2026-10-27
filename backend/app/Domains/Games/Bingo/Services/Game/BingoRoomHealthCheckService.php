<?php

declare(strict_types=1);

namespace App\Domains\Games\Bingo\Services\Game;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use Closure;
use DomainException;

/**
 * Valida que una sala esté lista para iniciar el sorteo.
 *
 * Centraliza las precondiciones que antes estaban dispersas entre
 * StartBingoDrawJob, BingoDrawService y BingoRoomCrudService::activateDraw().
 * Lanza excepciones descriptivas que el job puede registrar en el log.
 */
final class BingoRoomHealthCheckService
{
    /**
     * Ejecuta todas las comprobaciones pre-sorteo en orden de severidad.
     * Lanza \DomainException en la primera que falle.
     */
    public function assertReadyForDraw(BingoRoom $room) : void
    {
        foreach ($this->checks($room) as $check) {
            $check();
        }
    }

    /**
     * Devuelve todos los errores encontrados en lugar de lanzar en el primero.
     *
     * @return list<string> Lista de problemas (vacía = sala lista)
     */
    public function collectErrors(BingoRoom $room) : array
    {
        $errors = [];
        foreach ($this->checks($room) as $check) {
            try {
                $check();
            } catch (DomainException $e) {
                $errors[] = $e->getMessage();
            }
        }

        return $errors;
    }

    /**
     * Versión booleana — útil para condicionales sin try/catch.
     */
    public function isReadyForDraw(BingoRoom $room) : bool
    {
        try {
            $this->assertReadyForDraw($room);

            return true;
        } catch (DomainException) {
            return false;
        }
    }

    private function assertHasPlayers(BingoRoom $room) : void
    {
        if (($room->total_players ?? 0) <= 0) {
            throw new DomainException(
                "La sala #{$room->id} no tiene jugadores registrados.",
            );
        }
    }

    private function assertHasRounds(BingoRoom $room) : void
    {
        if ($room->rounds->isEmpty()) {
            throw new DomainException(
                "La sala #{$room->id} no tiene rondas configuradas.",
            );
        }
    }

    private function assertMinCardsReached(BingoRoom $room) : void
    {
        $minCards = $room->config?->min_cards_to_start;
        if ($minCards !== null && ($room->total_cards ?? 0) < $minCards) {
            throw new DomainException(
                "La sala #{$room->id} requiere mínimo {$minCards} cartones para sortear, tiene " . ($room->total_cards ?? 0) . '.',
            );
        }
    }

    private function assertMinPlayersReached(BingoRoom $room) : void
    {
        $minPlayers = $room->config?->min_players;
        if ($minPlayers !== null && ($room->total_players ?? 0) < $minPlayers) {
            throw new DomainException(
                "La sala #{$room->id} requiere mínimo {$minPlayers} jugadores para sortear, tiene " . ($room->total_players ?? 0) . '.',
            );
        }
    }

    private function assertNotAlreadyFinished(BingoRoom $room) : void
    {
        // Usa la relación ya eager-loaded — sin query adicional a la BD.
        // Comparar con el total configurado de rondas en lugar de solo comprobar
        // si existe alguna FINISHED: en salas multi-ronda, la existencia de una
        // ronda FINISHED es normal y no debe bloquear el inicio de la siguiente.
        $totalRounds = max(1, (int) ($room->config?->rounds ?? 1));
        $finishedRounds = $room->rounds->where('status', 'FINISHED')->count();

        if ($finishedRounds >= $totalRounds) {
            throw new DomainException(
                "La sala #{$room->id} ya tiene todas sus rondas FINISHED ({$finishedRounds}/{$totalRounds}) — el sorteo ya fue completado.",
            );
        }
    }

    private function assertPrizePoolCoherent(BingoRoom $room) : void
    {
        $prizePool = (float) ($room->prize_pool ?? 0);
        foreach ($room->rounds as $round) {
            if ($round->prize_type === 'PCT' && $prizePool <= 0) {
                throw new DomainException(
                    "La ronda #{$round->round} usa prize_type=PCT pero el prize_pool de la sala es 0.",
                );
            }
        }
    }

    private function assertRoundsHavePatterns(BingoRoom $room) : void
    {
        $missingPattern = $room->rounds->first(fn ($r) => empty($r->bingo_pattern_id));
        if ($missingPattern) {
            throw new DomainException(
                "La ronda #{$missingPattern->round} de la sala #{$room->id} no tiene patrón asignado.",
            );
        }
    }

    private function assertValidStatus(BingoRoom $room) : void
    {
        if (! in_array($room->status?->code, ['ACTIVE', 'IN_PROGRESS'], true)) {
            throw new DomainException(
                "Estado inválido para sorteo: '{$room->status?->code}'. Se requiere ACTIVE o IN_PROGRESS.",
            );
        }
    }

    /**
     * Lista canónica de comprobaciones. Usada por assertReadyForDraw y collectErrors
     * para garantizar que ambos siempre ejecuten exactamente los mismos checks.
     *
     * @return list<Closure>
     */
    private function checks(BingoRoom $room) : array
    {
        // Asegurar eager-load de rounds y config una sola vez antes de los checks
        $room->loadMissing(['rounds', 'config']);

        return [
            fn () => $this->assertValidStatus($room),
            fn () => $this->assertNotAlreadyFinished($room),
            fn () => $this->assertHasPlayers($room),
            fn () => $this->assertHasRounds($room),
            fn () => $this->assertRoundsHavePatterns($room),
            fn () => $this->assertMinCardsReached($room),
            fn () => $this->assertMinPlayersReached($room),
            fn () => $this->assertPrizePoolCoherent($room),
        ];
    }
}
