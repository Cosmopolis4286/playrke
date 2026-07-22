<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Castea una columna ENUM('ACTIVE','INACTIVE') a bool en PHP.
 *
 * El cast nativo 'boolean' de Eloquent lee bien ('ACTIVE' es truthy) pero al
 * escribir serializa true/false a 1/0, valores inválidos para el ENUM —
 * MySQL trunca con warning (o falla en modo estricto). Ver roles.status:
 * 2024_06_12_06_create_roles_table.php la define como
 * enum('ACTIVE','INACTIVE'), no como boolean/tinyint.
 */
class AsActiveStatusEnum implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): bool
    {
        return $value === 'ACTIVE';
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        return $value ? 'ACTIVE' : 'INACTIVE';
    }
}
