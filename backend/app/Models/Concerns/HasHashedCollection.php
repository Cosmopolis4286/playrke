<?php

namespace App\Models\Concerns;

use Illuminate\Support\Collection;

/**
 * Trait HasHashedCollection
 *
 * Provee utilidades para generar arrays de IDs hasheados
 * a partir de colecciones Eloquent.
 */
trait HasHashedCollection
{
    /**
     * Devuelve un array de IDs hasheados alineados con la colección.
     *
     * @return string[]
     */
    protected function hashCollectionIds(Collection $rows) : array
    {
        return $rows->map(function($model) {
            return method_exists($model, 'getHashedIdAttribute')
                ? $model->hashed_id
                : (string) $model->getKey();
        })->toArray();
    }

    /**
     * Devuelve un map id => hash (útil para Vue / Alpine).
     *
     * @return array<string, string>
     */
    protected function mapHashedCollectionIds(Collection $rows) : array
    {
        return $rows->mapWithKeys(function($model) {
            return [
                (string) $model->getKey() => $model->hashed_id,
            ];
        })->toArray();
    }
}
