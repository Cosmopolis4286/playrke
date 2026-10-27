<?php

namespace App\Models\Games\Common\Utils;

use App\Traits\SnowflakeId;
use BadMethodCallException;
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

    protected $casts = [
        'id' => 'string',
        'code' => 'string',
        'label' => 'string',
    ];

    /**
     * Los campos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'label', // Etiqueta legible (ej. 'Borrador', 'Activa')
    ];

    /**
     * Nombre de la tabla en la base de datos.
     *
     * @var string
     */
    protected $table = 'game_status';

    /**
     * Devuelve un mapa code→id cacheado por request (evita N queries por saveRoom/draw/etc.).
     * TTL de 60 s — los códigos de estado no cambian en producción.
     *
     * @param string[] $codes si se omite, devuelve todos los estados
     *
     * @return array<string,string>
     */
    public static function codesMap(array $codes = []) : array
    {
        $sorted = $codes;
        sort($sorted);
        $cacheKey = 'game_status_map_' . md5(implode(',', $sorted));

        try {
            return Cache::tags(['game_status'])->remember($cacheKey, 60, function() use ($codes) {
                $query = static::query();
                if (! empty($codes)) {
                    $query->whereIn('code', $codes);
                }

                return $query->pluck('id', 'code')->all();
            });
        } catch (BadMethodCallException) {
            // Driver no soporta tags (file/database/array) — cache sin tag, se invalida por TTL.
            return Cache::remember($cacheKey, 60, function() use ($codes) {
                $query = static::query();
                if (! empty($codes)) {
                    $query->whereIn('code', $codes);
                }

                return $query->pluck('id', 'code')->all();
            });
        }
    }

    /** Devuelve el ID de un único código de estado. */
    public static function codeToId(string $code) : ?string
    {
        return static::codesMap([$code])[$code] ?? null;
    }

    /**
     * Invalida la cache de codesMap()/codeToId(). Necesario tras sembrar o
     * modificar estados fuera del flujo normal de la app (seeders, tests con
     * DatabaseTransactions) — la cache de aplicación no se revierte junto con
     * una transacción SQL, así que un ID cacheado en una corrida anterior
     * puede sobrevivir a que la fila real ya no exista (o exista con otro id).
     */
    public static function forgetCache() : void
    {
        try {
            Cache::tags(['game_status'])->flush();
        } catch (BadMethodCallException) {
            // Driver sin soporte de tags (file/database/array, mismo fallback que
            // codesMap()): la clave del fallback depende de $codes ordenados, así
            // que no hay forma de invalidar selectivamente sin conocer de antemano
            // cada combinación de códigos usada. Se limpia todo el store — aceptable
            // porque este método solo se llama en seeders/tests, no en request path.
            Cache::flush();
        }
    }

    /**
     * Scope para buscar por ID del estado de juego.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     *
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeById(\Illuminate\Database\Eloquent\Builder $query, string $id) : \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('id', strtoupper($id));
    }

    /**
     * Scope para buscar por descripción parcial.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     *
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeByLabel(\Illuminate\Database\Eloquent\Builder $query, string $label) : \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('label', 'LIKE', "%{$label}%");
    }
}
