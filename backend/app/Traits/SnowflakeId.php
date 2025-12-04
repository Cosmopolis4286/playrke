<?php

namespace App\Traits;

use BradieTilley\Snowflake\Snowflake;

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

    public function getIncrementing()
    {
        return false;
    }

    public function getKeyType()
    {
        return 'string';
    }

    /**
     * Genera un ID único usando Snowflake (API real de tu paquete).
     */
    public static function generateSnowflakeId(): string
    {
        $snowflake = new Snowflake();
        return (string) $snowflake->id();
    }
}
