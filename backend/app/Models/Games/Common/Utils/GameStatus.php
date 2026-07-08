<?php

namespace App\Models\Games\Common\Utils;

use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Modelo para la gestión de estados de juegos.
 *
 * Representa los diferentes estados que puede tener un juego
 * (ej. INACTIVE, ACTIVE, FINISHED, CANCELLED, etc.)
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
        'code',
        'label', // Etiqueta legible (ej. 'Borrador', 'Activa')
    ];

    protected $casts = [
        'id' => 'string',
        'code' => 'string',
        'label' => 'string'
    ];

    /**
     * Devuelve un mapa code→id cacheado por request (evita N queries por saveRoom/draw/etc.).
     * TTL de 60 s — los códigos de estado no cambian en producción.
     *
     * @param string[] $codes  Si se omite, devuelve todos los estados.
     * @return array<string,string>
     */
    public static function codesMap(array $codes = []): array
    {
        $cacheKey = 'game_status_map_' . md5(implode(',', $codes));

        return Cache::remember($cacheKey, 60, function () use ($codes) {
            $query = static::query();
            if (!empty($codes)) {
                $query->whereIn('code', $codes);
            }
            return $query->pluck('id', 'code')->all();
        });
    }

    /** Devuelve el ID de un único código de estado. */
    public static function codeToId(string $code): ?string
    {
        return static::codesMap([$code])[$code] ?? null;
    }

    /**
     * Scope para buscar por ID del estado de juego.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     * @param string $id
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeById(\Illuminate\Database\Eloquent\Builder $query, string $id): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('id', strtoupper($id));
    }

    /**
     * Scope para buscar por descripción parcial.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     * @param string $label
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeByLabel(\Illuminate\Database\Eloquent\Builder $query, string $label): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('label', 'LIKE', "%{$label}%");
    }
}
