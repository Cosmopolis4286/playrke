<?php

namespace App\View\Components\Concerns;

use Illuminate\Support\Collection;
use Vinkla\Hashids\Facades\Hashids;

/**
 * Genera y cachea IDs encriptados durante el ciclo del request.
 */
trait GeneratesHashedIds
{
    /**
     * Cache interno por request.
     *
     * @var array<string,string>
     */
    protected array $hashCache = [];

    /**
     * Devuelve IDs hasheados para una colección de modelos.
     * Cachea resultados durante el request.
     *
     * @param Collection $rows
     * @return string[]
     */
    protected function hashCollectionIds(Collection $rows): array
    {
        return $rows->pluck('id')->map(function ($id) {
            return $this->hashId($id);
        })->toArray();
    }

    /**
     * Hashea un ID individual usando cache por request.
     *
     * @param string|int $id
     * @return string
     */
    protected function hashId(string|int $id): string
    {
        if (!isset($this->hashCache[$id])) {
            $this->hashCache[$id] = Hashids::connection('main')->encode($id);
        }

        return $this->hashCache[$id];
    }
}
