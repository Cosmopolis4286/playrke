<?php

namespace App\Repositories\Games\Shared\PrizePool;

use App\Models\Games\Shared\PrizePool\PrizePoolType;

class PrizePoolTypeRepository
{
    /**
     * Obtener registros por un arreglo de IDs.
     */
    public function getByIds(array $ids)
    {
        return PrizePoolType::query()
            ->whereIn('id', $ids)
            ->get();
    }

    /**
     * Obtener todos los PrizePoolTypes activos (si tienes campo status).
     */
    public function getActive()
    {
        return PrizePoolType::query()
            ->where('status', 'ACTIVE')
            ->get();
    }

    /**
     * Crear o actualizar un PrizePoolType.
     */
    public function upsert(array $attributes, array $values = [])
    {
        return PrizePoolType::updateOrCreate($attributes, $values);
    }
}
