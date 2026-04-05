<?php

namespace App\Traits;

use BradieTilley\Snowflake\Snowflake;

/**
 * Trait para modelos que usan IDs de Snowflake.
 * 
 * NOTA: Los modelos que usen este trait deben definir:
 * - protected $keyType = 'string';
 * - public $incrementing = false;
 * 
 * Esto asegura que los IDs se serialicen como strings en JSON,
 * previniendo pérdida de precisión en JavaScript con números grandes.
 */
trait SnowflakeId
{
    protected static function bootSnowflakeId()
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = self::generateSnowflakeId();
            }
        });
    }

    /**
     * Genera un ID único usando Snowflake.
     */
    public static function generateSnowflakeId(): string
    {
        $snowflake = new Snowflake();
        return (string) $snowflake->id();
    }
}
