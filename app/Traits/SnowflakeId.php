<?php

namespace App\Traits;

use BradieTilley\Snowflake\Snowflake;

/**
 * Trait SnowflakeId
 *
 * Añade soporte para generar IDs únicos usando Snowflake
 * en modelos de Eloquent.
 */
trait SnowflakeId
{
    /**
     * Boot del trait para generar Snowflake IDs
     */
    protected static function bootSnowflakeId(): void
    {
        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (new Snowflake())->id();
            }
        });
    }
}
