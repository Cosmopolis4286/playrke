<?php
declare(strict_types=1);

namespace App\Domains\Games\Bingo\Services\Game;

use App\Domains\Games\Bingo\Models\Draw\BingoDraw;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Round\BingoRound;

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
     *
     * @throws \DomainException con un mensaje que describe exactamente qué falló.
     */
    public function assertReadyForDraw(BingoRoom $room): void
    {
        $this->assertValidStatus($room);
        $this->assertNotAlreadyFinished($room);
        $this->assertHasPlayers($room);
        $this->assertHasRounds($room);
        $this->assertRoundsHavePatterns($room);
        $this->assertMinCardsReached($room);
        $this->assertPrizePoolCoherent($room);
    }

    /**
     * Versión booleana — útil para condicionales sin try/catch.
     */
    public function isReadyForDraw(BingoRoom $room): bool
    {
        try {
            $this->assertReadyForDraw($room);
            return true;
        } catch (\DomainException) {
            return false;
        }
    }

    /**
     * Devuelve todos los errores encontrados en lugar de lanzar en el primero.
     *
     * @return list<string> Lista de problemas (vacía = sala lista)
     */
    public function collectErrors(BingoRoom $room): array
    {
        $checks = [
            fn() => $this->assertValidStatus($room),
            fn() => $this->assertNotAlreadyFinished($room),
            fn() => $this->assertHasPlayers($room),
            fn() => $this->assertHasRounds($room),
            fn() => $this->assertRoundsHavePatterns($room),
            fn() => $this->assertMinCardsReached($room),
            fn() => $this->assertPrizePoolCoherent($room),
        ];

        $errors = [];
        foreach ($checks as $check) {
            try {
                $check();
            } catch (\DomainException $e) {
                $errors[] = $e->getMessage();
            }
        }

        return $errors;
    }

    private function assertValidStatus(BingoRoom $room): void
    {
        if (!in_array($room->status?->code, ['ACTIVE', 'IN_PROGRESS'], true)) {
            throw new \DomainException(
                "Estado inválido para sorteo: '{$room->status?->code}'. Se requiere ACTIVE o IN_PROGRESS."
            );
        }
    }

    private function assertNotAlreadyFinished(BingoRoom $room): void
    {
        if (BingoRound::where('room_id', (string) $room->id)->where('status', 'FINISHED')->exists()) {
            throw new \DomainException(
                "La sala #{$room->id} ya tiene rondas FINISHED — el sorteo ya fue completado."
            );
        }
    }

    private function assertHasPlayers(BingoRoom $room): void
    {
        if (($room->total_players ?? 0) <= 0) {
            throw new \DomainException(
                "La sala #{$room->id} no tiene jugadores registrados."
            );
        }
    }

    private function assertHasRounds(BingoRoom $room): void
    {
        $room->loadMissing('rounds');
        if ($room->rounds->isEmpty()) {
            throw new \DomainException(
                "La sala #{$room->id} no tiene rondas configuradas."
            );
        }
    }

    private function assertRoundsHavePatterns(BingoRoom $room): void
    {
        $room->loadMissing('rounds');
        $missingPattern = $room->rounds->first(fn($r) => empty($r->bingo_pattern_id));
        if ($missingPattern) {
            throw new \DomainException(
                "La ronda #{$missingPattern->round} de la sala #{$room->id} no tiene patrón asignado."
            );
        }
    }

    private function assertMinCardsReached(BingoRoom $room): void
    {
        $room->loadMissing('config');
        $minCards = $room->config?->min_cards_to_start;
        if ($minCards !== null && ($room->total_cards ?? 0) < $minCards) {
            throw new \DomainException(
                "La sala #{$room->id} requiere mínimo {$minCards} cartones para sortear, tiene " . ($room->total_cards ?? 0) . "."
            );
        }
    }

    private function assertPrizePoolCoherent(BingoRoom $room): void
    {
        $room->loadMissing(['rounds', 'config']);
        $prizePool = (float) ($room->prize_pool ?? 0);

        foreach ($room->rounds as $round) {
            if ($round->prize_type === 'PCT' && $prizePool <= 0) {
                throw new \DomainException(
                    "La ronda #{$round->round} usa prize_type=PCT pero el prize_pool de la sala es 0."
                );
            }
        }
    }
}
