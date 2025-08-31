<?php

namespace App\Models\Games\Shared\Utils;

use Illuminate\Database\Eloquent\Model;

class GameStatus extends Model
{
    /**
     * Nombre de la tabla asociada al modelo.
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

    /**
     * Indica que la tabla tiene timestamps (created_at y updated_at)
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Retorna el estado como un array listo para usar en selects o enums dinámicos.
     *
     * @return array<string, string>
     */
    public static function toArrayForSelect(): array
    {
        return self::all()->pluck('description', 'value')->toArray();
    }
}
