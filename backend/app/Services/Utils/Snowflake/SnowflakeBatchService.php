<?php

namespace App\Services\Utils\Snowflake;

use BradieTilley\Snowflake\Snowflake;

class SnowflakeBatchService
{
    protected Snowflake $snowflake;

    public function __construct()
    {
        // Instancia única de Snowflake
        $this->snowflake = new Snowflake();
    }

    /**
     * Genera un solo ID
     */
    public function nextId(): string
    {
        return (string) $this->snowflake->id();
    }

    /**
     * Genera múltiples IDs en lote
     *
     * @param int $count Cantidad de IDs a generar
     * @return string[]
     */
    public function batch(int $count): array
    {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $ids[] = (string) $this->snowflake->id();
        }
        return $ids;
    }
}
