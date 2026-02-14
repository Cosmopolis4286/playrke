<?php

namespace App\Models\Games\Common\Utils;

use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la gestión de estados de juegos.
 *
 * Representa los diferentes estados que puede tener un juego
 * (ej. DRAFT, ACTIVE, FINISHED, CANCELLED, etc.)
 */
class GameStatus extends Model
{
    use SnowflakeId;

    /**
     * Nombre de la tabla en la base de datos.
     *
     * @var string
     */
    protected $table = 'game_status';

    /**
     * Los campos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'value',       // Valor interno del estado (ej. 'DRAFT', 'ACTIVE')
        'description', // Etiqueta legible (ej. 'Borrador', 'Activa')
    ];

    protected $casts = [
        'id' => 'string',
        'value' => 'string',
        'description' => 'string',
    ];

    /**
     * Accessor para obtener el valor en mayúsculas.
     *
     * Garantiza que el valor siempre se almacene en mayúsculas
     * para mantener consistencia en la base de datos.
     *
     * @param string $value
     * @return string
     */
    public function setValueAttribute(string $value): void
    {
        $this->attributes['value'] = strtoupper($value);
    }

    /**
     * Scope para buscar por valor de estado.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     * @param string $value
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeByValue(\Illuminate\Database\Eloquent\Builder $query, string $value): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('value', strtoupper($value));
    }

    /**
     * Scope para buscar por descripción parcial.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     * @param string $description
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeByDescription(\Illuminate\Database\Eloquent\Builder $query, string $description): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('description', 'LIKE', "%{$description}%");
    }
}
