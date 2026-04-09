<?php

namespace App\Traits;

use BradieTilley\Snowflake\Snowflake;

/**
 * Trait para modelos que usan IDs Snowflake.
 *
 * Configura automáticamente:
 * - $keyType = 'string'
 * - $incrementing = false
 * - cast 'id' => 'string' (via método casts() de Laravel 11)
 *
 * El cast garantiza que el ID se serialice como string en toArray(),
 * toJson() y JsonResponse, evitando pérdida de precisión en JS (>MAX_SAFE_INTEGER).
 */
trait SnowflakeId
{
    public function initializeSnowflakeId(): void
    {
        $this->keyType    = 'string';
        $this->incrementing = false;
    }

    /**
     * Fuerza 'id' => 'string' en todos los modelos que usen este trait.
     * getCasts() es el método que Eloquent llama internamente para aplicar
     * casts en toArray(), toJson() y JsonResponse.
     */
    public function getCasts(): array
    {
        return array_merge(parent::getCasts(), ['id' => 'string']);
    }

    protected static function bootSnowflakeId(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) (new Snowflake())->id();
            }
        });
    }
}
