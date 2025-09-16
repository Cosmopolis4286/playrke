<?php

namespace App\Models\Games\Shared\Room;

use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la gestión de salas de juego.
 *
 * Representa las diferentes salas disponibles en el sistema,
 * con tipos específicos como privadas y públicas.
 */
class Room extends Model
{
    use SnowflakeId;

    /**
     * Tipos de sala disponibles en el sistema.
     *
     * @var array<string, string>
     */
    public const TYPES = [
        'PV' => 'Privada',
        'PB' => 'Pública'
    ];

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'rooms';

    /**
     * Los campos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',         // Tipo de sala (PV|PB)
        'name',         // Nombre de la sala
        'description',  // Descripción de la sala
        'capacity',     // Capacidad máxima
        'status',       // Estado de la sala
    ];

    /**
     * Los atributos que deben ser casteados a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'capacity' => 'integer',
        'status' => 'boolean',
    ];

    /**
     * Scope para filtrar por tipo de sala.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para obtener salas privadas.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePrivate($query)
    {
        return $query->where('type', 'PV');
    }

    /**
     * Scope para obtener salas públicas.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublic($query)
    {
        return $query->where('type', 'PB');
    }

    /**
     * Scope para filtrar por estado.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param bool $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, bool $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para obtener salas activas.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope para ordenar por tipo.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction Dirección del ordenamiento (asc|desc)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByType($query, string $direction = 'asc')
    {
        return $query->orderBy('type', $direction);
    }

    /**
     * Verificar si la sala es privada.
     *
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this->type === 'PV';
    }

    /**
     * Verificar si la sala es pública.
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->type === 'PB';
    }

    /**
     * Verificar si la sala está activa.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === true;
    }

    /**
     * Verificar si la sala está inactiva.
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        return $this->status === false;
    }

    /**
     * Obtener la descripción del tipo de sala.
     *
     * @return string
     */
    public function getTypeDescription(): string
    {
        return self::TYPES[$this->type] ?? 'Desconocido';
    }

    /**
     * Obtener información del estado de la sala.
     *
     * @return string
     */
    public function getStatusLabel(): string
    {
        return $this->isActive() ? 'Activa' : 'Inactiva';
    }

    /**
     * Activar la sala.
     *
     * @return bool
     */
    public function activate(): bool
    {
        return $this->update(['status' => true]);
    }

    /**
     * Desactivar la sala.
     *
     * @return bool
     */
    public function deactivate(): bool
    {
        return $this->update(['status' => false]);
    }

    /**
     * Verificar si un tipo de sala es válido.
     *
     * @param string $type Tipo a verificar
     * @return bool
     */
    public static function isValidType(string $type): bool
    {
        return array_key_exists($type, self::TYPES);
    }

    /**
     * Obtener todos los tipos de sala disponibles.
     *
     * @param string|null $excludeType Tipo a excluir (opcional)
     * @return array<string, string>
     */
    public static function getRoomTypes(?string $excludeType = null): array
    {
        if ($excludeType) {
            return array_filter(self::TYPES, function ($type) use ($excludeType) {
                return $type !== $excludeType;
            }, ARRAY_FILTER_USE_KEY);
        }

        return self::TYPES;
    }

    /**
     * Obtener la descripción de un tipo específico.
     *
     * @param string $type Código del tipo
     * @return string|null
     */
    public static function getTypeDescriptionByCode(string $type): ?string
    {
        return self::TYPES[$type] ?? null;
    }

    /**
     * Obtener el nombre completo de la sala (con tipo).
     *
     * @return string
     */
    public function getFullName(): string
    {
        $typeDesc = $this->getTypeDescription();
        $name = $this->name ?: "Sala #{$this->id}";

        return "{$name} ({$typeDesc})";
    }

    /**
     * Verificar si la sala tiene capacidad disponible.
     *
     * @param int $requiredCapacity Capacidad requerida
     * @return bool
     */
    public function hasCapacity(int $requiredCapacity = 1): bool
    {
        return $this->capacity >= $requiredCapacity;
    }

    /**
     * Obtener información de capacidad formateada.
     *
     * @return string
     */
    public function getCapacityInfo(): string
    {
        if ($this->capacity) {
            return "Capacidad: {$this->capacity} personas";
        }

        return 'Capacidad: Sin límite';
    }

    /**
     * Mutator para validar y normalizar el tipo antes de guardar.
     *
     * @param string $value
     * @return void
     */
    public function setTypeAttribute(string $value): void
    {
        $normalizedType = strtoupper(trim($value));

        if (!self::isValidType($normalizedType)) {
            $validTypes = implode(', ', array_keys(self::TYPES));
            throw new \InvalidArgumentException("Tipo de sala inválido. Tipos válidos: {$validTypes}");
        }

        $this->attributes['type'] = $normalizedType;
    }

    /**
     * Mutator para limpiar el nombre antes de guardar.
     *
     * @param string|null $value
     * @return void
     */
    public function setNameAttribute(?string $value): void
    {
        $this->attributes['name'] = $value ? trim($value) : null;
    }

    /**
     * Mutator para limpiar la descripción antes de guardar.
     *
     * @param string|null $value
     * @return void
     */
    public function setDescriptionAttribute(?string $value): void
    {
        $this->attributes['description'] = $value ? trim($value) : null;
    }

    /**
     * Accessor para obtener información completa de la sala.
     *
     * @return string
     */
    public function getFullInfoAttribute(): string
    {
        $fullName = $this->getFullName();
        $status = $this->getStatusLabel();
        $capacity = $this->getCapacityInfo();

        return "{$fullName} - {$status} - {$capacity}";
    }

    /**
     * Accessor para obtener información resumida.
     *
     * @return string
     */
    public function getSummaryAttribute(): string
    {
        return sprintf(
            '%s [%s - %s]',
            $this->getFullName(),
            $this->getTypeDescription(),
            $this->isActive() ? 'ACTIVA' : 'INACTIVA'
        );
    }

    /**
     * Boot del modelo para eventos.
     */
    protected static function boot()
    {
        parent::boot();

        // Validar tipo antes de crear
        static::creating(function ($room) {
            if (!self::isValidType($room->type)) {
                throw new \InvalidArgumentException('Tipo de sala inválido al crear la sala.');
            }
        });

        // Validar tipo antes de actualizar
        static::updating(function ($room) {
            if ($room->isDirty('type') && !self::isValidType($room->type)) {
                throw new \InvalidArgumentException('Tipo de sala inválido al actualizar la sala.');
            }
        });
    }
}
