<?php

namespace App\Models\Games\Bingo\Pattern;

use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para los patrones de bingo
 *
 * Representa los patrones de cartones de bingo utilizados en el juego.
 * Incluye información sobre tipo de patrón, nombre, usuario, cajas y estado.
 *
 * @package App\Models\Games\Bingo\Pattern
 * @author Sistema PlayRKE
 * @version 1.0
 *
 * @property string $id ID único del patrón
 * @property string $type_pattern Tipo de patrón (UNIQUE, MULTIPLE)
 * @property string $name Nombre del patrón
 * @property string $user_id ID del usuario creador
 * @property string $boxes Cajas del patrón separadas por ';'
 * @property string $status Estado del patrón (ACTIVE, INACTIVE)
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de actualización
 *
 * @property-read User $userOwner Usuario propietario del patrón
 */
class BingoPattern extends Model
{
    use SnowflakeId;

    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'bingo_patterns';

    /**
     * Tipos de patrones disponibles
     */
    public const TYPES = [
        'UNIQUE' => 'Único',
        'MULTIPLE' => 'Múltiple',
    ];

    /**
     * Estados disponibles para los patrones
     */
    public const STATUSES = [
        'ACTIVE' => 'Activo',
        'INACTIVE' => 'Inactivo',
    ];

    /**
     * Tipos como constantes
     */
    public const TYPE_UNIQUE   = 'UNIQUE';
    public const TYPE_MULTIPLE = 'MULTIPLE';

    /**
     * Rango de num_id reservado para patrones de sistema (default_pattern='Y').
     * Los patrones de usuario deben usar num_id >= SYSTEM_NUM_ID_MAX + 1.
     */
    public const SYSTEM_NUM_ID_MIN = 1;
    public const SYSTEM_NUM_ID_MAX = 30;

    /**
     * Estados como constantes
     */
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    /**
     * Campos que pueden ser asignados masivamente
     */
    protected $fillable = [
        'num_id',
        'name',
        'default_pattern',
        'type_pattern',
        'user_id',
        'boxes',
        'status',
    ];

    /**
     * Campos que deben ser convertidos a tipos específicos
     */
    protected $casts = [
        'id'             => 'string',
        'default_pattern' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Campos que se agregan automáticamente al modelo
     */
    protected $appends = [
        'type_description',
        'status_description',
        'boxes_array',
        'boxes_count',
        'status_badge'
    ];

    /**
     * Relación con el usuario propietario
     *
     * @return BelongsTo<User, BingoPattern>
     */
    public function userOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope para obtener patrones por usuario
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para obtener patrones por tipo
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type_pattern', $type);
    }

    /**
     * Scope para obtener patrones por estado
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para obtener patrones activos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope para obtener patrones inactivos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    /**
     * Scope para obtener patrones únicos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnique($query)
    {
        return $query->where('type_pattern', self::TYPE_UNIQUE);
    }

    /**
     * Scope para obtener patrones múltiples
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMultiple($query)
    {
        return $query->where('type_pattern', self::TYPE_MULTIPLE);
    }

    /**
     * Scope para obtener patrones por nombre
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $name
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    /**
     * Accessor para obtener la descripción del tipo
     *
     * @return string
     */
    public function getTypeDescriptionAttribute(): string
    {
        return self::TYPES[$this->type_pattern] ?? $this->type_pattern ?? '';
    }

    /**
     * Accessor para el status con badge HTML
     *
     * @return string
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'ACTIVE' => '<span class="badge badge-success">Activo</span>',
            'INACTIVE' => '<span class="badge badge-secondary">Inactivo</span>',
            'BLOCKED' => '<span class="badge badge-danger">Bloqueado</span>',
            'DELETED' => '<span class="badge badge-dark">Eliminado</span>',
            default => '<span class="badge badge-warning">Desconocido</span>'
        };
    }

    /**
     * Accessor para obtener la descripción del estado
     *
     * @return string
     */
    public function getStatusDescriptionAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status ?? '';
    }

    /**
     * Accessor para obtener las cajas como array
     *
     * @return array
     */
    public function getBoxesArrayAttribute(): array
    {
        return $this->boxes ? array_filter(explode(';', $this->boxes)) : [];
    }

    /**
     * Accessor para obtener el conteo de cajas
     *
     * @return int
     */
    public function getBoxesCountAttribute(): int
    {
        return count($this->boxes_array);
    }

    /**
     * Accessor para verificar si está activo
     *
     * @return bool
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Accessor para verificar si está inactivo
     *
     * @return bool
     */
    public function getIsInactiveAttribute(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    /**
     * Accessor para verificar si es tipo único
     *
     * @return bool
     */
    public function getIsUniqueAttribute(): bool
    {
        return $this->type_pattern === self::TYPE_UNIQUE;
    }

    /**
     * Accessor para verificar si es tipo múltiple
     *
     * @return bool
     */
    public function getIsMultipleAttribute(): bool
    {
        return $this->type_pattern === self::TYPE_MULTIPLE;
    }

    /**
     * Obtener el siguiente num_id disponible para un usuario dado.
     *
     * Los patrones de sistema ocupan 1–SYSTEM_NUM_ID_MAX.
     * Los patrones de usuario empiezan en SYSTEM_NUM_ID_MAX + 1.
     *
     * @param string $userId
     * @return int
     */
    public static function nextNumIdForUser(string $userId): int
    {
        $max = static::where('user_id', $userId)
            ->where('num_id', '>', self::SYSTEM_NUM_ID_MAX)
            ->max('num_id');

        return $max ? $max + 1 : self::SYSTEM_NUM_ID_MAX + 1;
    }

    /**
     * Obtener columnas y cajas formateadas
     *
     * @return array{type: string, columns: array}
     */
    public function getFormattedColumns(): array
    {
        if (empty($this->boxes)) {
            return ['type' => $this->type_pattern, 'columns' => []];
        }

        if ($this->is_unique) {
            return [
                'type' => $this->type_pattern,
                'columns' => array_values(array_unique($this->boxes_array))
            ];
        }

        // Los boxes de un patrón MULTIPLE contienen num_id de otros patrones.
        // Se busca primero en patrones de sistema (user_id=NULL), luego en los del mismo usuario.
        $columns = [];
        foreach ($this->boxes_array as $numId) {
            $numId = (int) $numId;
            $pattern = static::where('num_id', $numId)
                ->where(function ($q) {
                    $q->whereNull('user_id')
                        ->orWhere('user_id', $this->user_id);
                })
                ->first();

            if ($pattern && $pattern->boxes) {
                $boxArray = array_values(array_unique(array_filter(explode(';', $pattern->boxes))));
                $columns[$numId] = $boxArray;
            }
        }

        return ['type' => $this->type_pattern, 'columns' => $columns];
    }

    /**
     * Validar formato de cajas
     *
     * @return bool
     */
    public function isValidBoxesFormat(): bool
    {
        if (empty($this->boxes)) {
            return false;
        }

        foreach ($this->boxes_array as $box) {
            if (!is_numeric($box) || $box < 1 || $box > 75) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verificar si el patrón es válido
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return !empty($this->name) &&
            !empty($this->type_pattern) &&
            !empty($this->boxes) &&
            $this->isValidBoxesFormat();
    }

    /**
     * Obtener cajas como string formateado
     *
     * @return string
     */
    public function getFormattedBoxes(): string
    {
        return implode(', ', $this->boxes_array);
    }
}
