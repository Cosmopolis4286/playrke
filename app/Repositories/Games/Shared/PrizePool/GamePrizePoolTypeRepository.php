<?php

namespace App\Repositories\Games\Shared\PrizePool;

use App\Models\Games\Shared\PrizePool\GamePrizePoolType;

class GamePrizePoolTypeRepository
{
    /**
     * Obtener los tipos de prize pool disponibles por juego y modo de juego.
     */
    public function getAvailableByGameMode(int|string $gameId, int|string $gameModeId): array
    {
        return GamePrizePoolType::query()
            ->where('game_id', $gameId)
            ->where('game_mode_id', $gameModeId)
            ->where('status', 'ACTIVE')
            ->distinct()
            ->pluck('prize_pool_type_id')
            ->toArray();
    }

    /**
     * Obtener todos los registros activos por juego.
     */
    public function getActiveByGame(int|string $gameId)
    {
        return GamePrizePoolType::query()
            ->where('game_id', $gameId)
            ->where('status', 'ACTIVE')
            ->get();
    }

    /**
     * Crear o actualizar un registro.
     */
    public function upsert(array $attributes, array $values = [])
    {
        return GamePrizePoolType::updateOrCreate($attributes, $values);
    }
}
