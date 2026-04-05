<?php

namespace App\Models;

use App\Traits\SnowflakeId;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Modelo Permission personalizado con soporte para Snowflake IDs como strings.
 *
 * Extiende el modelo de Spatie para mantener compatibilidad con laravel-permission,
 * mientras se asegura que los IDs se serialicen como strings en JSON.
 */
class Permission extends SpatiePermission
{
    use SnowflakeId;

    /**
     * Deshabilitar auto-incremento ya que usamos Snowflake IDs.
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    /**
     * Especificar que el tipo de llave es string para serialización JSON.
     * Esto previene la pérdida de precisión en JavaScript con números grandes.
     */
    public function getKeyType(): string
    {
        return 'string';
    }
}
