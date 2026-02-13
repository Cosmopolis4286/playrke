<?php

namespace App\Models\Games\Common\Utils;

use App\Models\Game;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo GameConfig refactorizado
 *
 * Solo contiene:
 * - Configuración de la tabla y propiedades
 * - Relaciones Eloquent
 * - Boot methods para Snowflake
 * - Accessors y Mutators si es necesario
 * - Scopes útiles
 *
 * NOTA: Las consultas complejas se han movido a GameConfigRepository
 */
class GameConfig extends Model
{
    use SnowflakeId;

    // ========================
    // Configuración de la tabla
    // ========================
    protected $table = 'game_configs';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    // ========================
    // Campos asignables
    // ========================
    protected $fillable = [
        'game_id',
        'param',
        'value',
        'description',
        'status',
    ];

    // ========================
    // Casting de tipos
    // ========================
    protected $casts = [
        'id' => 'string',
        'game_id' => 'string',
        'param' => 'string',
        'value' => 'string',
        'description' => 'string',
        'status' => 'boolean',
    ];

    // ========================
    // Relaciones
    // ========================

    /**
     * Relación: una configuración pertenece a un juego
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    // ========================
    // Accessors
    // ========================

    /**
     * Verificar si la configuración está activa
     */
    public function getIsActiveStatusAttribute(): bool
    {
        return $this->status === true;
    }

    /**
     * Obtener nombre para mostrar
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->param}: {$this->value}";
    }

    /**
     * Obtener el valor como entero
     */
    public function getIntValueAttribute(): int
    {
        return (int) $this->value;
    }

    /**
     * Obtener el valor como flotante
     */
    public function getFloatValueAttribute(): float
    {
        return (float) $this->value;
    }

    /**
     * Obtener el valor como booleano
     */
    public function getBoolValueAttribute(): bool
    {
        return in_array(strtolower($this->value), ['true', '1', 'yes', 'on', 'enabled']);
    }

    /**
     * Verificar si el valor es numérico
     */
    public function getIsNumericValueAttribute(): bool
    {
        return is_numeric($this->value);
    }

    /**
     * Obtener descripción o parámetro como fallback
     */
    public function getDescriptionOrParamAttribute(): string
    {
        return $this->description ?: $this->param;
    }

    // ========================
    // Scopes útiles
    // ========================

    /**
     * Scope para configuraciones activas
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope para configuraciones inactivas
     */
    public function scopeInactive($query)
    {
        return $query->where('status', false);
    }

    /**
     * Scope para buscar por juego
     */
    public function scopeForGame($query, string $gameId)
    {
        return $query->where('game_id', $gameId);
    }

    /**
     * Scope para buscar por parámetro
     */
    public function scopeByParam($query, string $param)
    {
        return $query->where('param', $param);
    }

    /**
     * Scope para buscar por valor
     */
    public function scopeByValue($query, string $value)
    {
        return $query->where('value', $value);
    }

    /**
     * Scope para ordenar por parámetro
     */
    public function scopeOrderedByParam($query)
    {
        return $query->orderBy('param');
    }

    /**
     * Scope para ordenar por valor
     */
    public function scopeOrderedByValue($query)
    {
        return $query->orderBy('value');
    }

    /**
     * Scope para buscar por juego y parámetro
     */
    public function scopeGameParam($query, string $gameId, string $param)
    {
        return $query->where('game_id', $gameId)->where('param', $param);
    }

    /**
     * Scope para buscar en descripción
     */
    public function scopeSearchDescription($query, string $search)
    {
        return $query->where('description', 'like', "%{$search}%");
    }

    /**
     * Scope con relación de juego
     */
    public function scopeWithGame($query)
    {
        return $query->with('game');
    }

    /**
     * Scope para valores booleanos
     */
    public function scopeBooleanValues($query)
    {
        return $query->whereIn('value', ['true', 'false', '1', '0', 'yes', 'no', 'on', 'off', 'enabled', 'disabled']);
    }

    /**
     * Scope para valores numéricos
     */
    public function scopeNumericValues($query)
    {
        return $query->whereRaw('value REGEXP \'^[0-9]+(\.[0-9]+)?$\'');
    }
}
