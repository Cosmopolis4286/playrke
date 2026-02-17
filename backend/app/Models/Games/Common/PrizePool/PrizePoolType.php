<?php

namespace App\Models\Games\Common\PrizePool;

use App\Models\Games\Common\PrizePool\GamePrizePoolType;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Modelo para la gestión de tipos de pool de premios.
 *
 * Representa los diferentes tipos de pools de premios disponibles
 * en el sistema, con información de descripción y estado.
 */
class PrizePoolType extends Model
{
    use SnowflakeId;
    /**
     * Los campos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',      // Código único del tipo de pool de premios
        'label',  // Etiqueta del tipo de pool de premios
        'status',    // Estado del tipo enum (ACTIVE/INACTIVE)
    ];

    /**
     * Los atributos que deben ser casteados a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',  // Casteo del ID a string para manejar Snowflake
        'status' => 'string',  // Casteo del estado a string
    ];

    /**
     * Relación con las asignaciones de juegos y pool de premios.
     *
     * @return HasMany
     */
    public function gamePrizePoolTypes(): HasMany
    {
        return $this->hasMany(GamePrizePoolType::class, 'prize_pool_type_id');
    }

    /**
     * Scope para obtener tipos activos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Scope para obtener tipos inactivos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'INACTIVE');
    }

    /**
     * Scope para filtrar por estado.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status Estado a filtrar (ACTIVE/INACTIVE)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para buscar por descripción.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $label
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByLabel($query, string $label)
    {
        return $query->where('label', 'LIKE', "%{$label}%");
    }

    /**
     * Scope para ordenar por descripción.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction Dirección del ordenamiento (asc|desc)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByLabel($query, string $direction = 'asc')
    {
        return $query->orderBy('label', $direction);
    }

    /**
     * Verificar si el tipo está activo.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === true;
    }

    /**
     * Verificar si el tipo está inactivo.
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        return $this->status === 'INACTIVE';
    }

    /**
     * Activar el tipo de pool de premios.
     *
     * @return bool
     */
    public function activate(): bool
    {
        return $this->update(['status' => 'ACTIVE']);
    }

    /**
     * Desactivar el tipo de pool de premios.
     *
     * @return bool
     */
    public function deactivate(): bool
    {
        return $this->update(['status' => 'INACTIVE']);
    }

    /**
     * Obtener la descripción normalizada.
     *
     * @return string
     */
    public function getNormalizedLabel(): string
    {
        return Str::title(trim($this->label));
    }

    /**
     * Obtener información del estado del tipo.
     *
     * @return string
     */
    public function getStatusLabel(): string
    {
        return $this->isActive() ? 'Activo' : 'Inactivo';
    }

    /**
     * Verificar si el tipo está siendo utilizado en algún juego.
     *
     * @return bool
     */
    public function isInUse(): bool
    {
        return $this->gamePrizePoolTypes()->exists();
    }

    /**
     * Obtener el número de juegos que usan este tipo.
     *
     * @return int
     */
    public function getUsageCount(): int
    {
        return $this->gamePrizePoolTypes()->count();
    }

    /**
     * Obtener el número de juegos únicos que usan este tipo.
     *
     * @return int
     */
    public function getUniqueGameCount(): int
    {
        return $this->gamePrizePoolTypes()->distinct('game_id')->count();
    }

    /**
     * Verificar si la descripción es válida.
     *
     * @param string $label Descripción a validar
     * @return bool
     */
    public static function isValidLabel(string $label): bool
    {
        $trimmed = trim($label);

        // Verificar longitud
        if (strlen($trimmed) < 2 || strlen($trimmed) > 50) {
            return false;
        }

        // Verificar caracteres válidos
        if (!preg_match('/^[\p{L}\p{N}\s\-_]+$/u', $trimmed)) {
            return false;
        }

        return true;
    }

    /**
     * Normalizar una descripción.
     *
     * @param string $label Descripción a normalizar
     * @return string Descripción normalizada
     */
    public static function normalizeLabel(string $label): string
    {
        // Limpiar espacios extra
        $normalized = trim($label);

        // Convertir a título
        $normalized = Str::title($normalized);

        // Reemplazar múltiples espacios con uno solo
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return $normalized;
    }

    /**
     * Mutator para normalizar la descripción antes de guardar.
     *
     * @param string $value
     * @return void
     */
    public function setLabelAttribute(string $value): void
    {
        $this->attributes['label'] = self::normalizeLabel($value);
    }

    /**
     * Accessor para obtener información completa del tipo.
     *
     * @return string
     */
    public function getFullInfoAttribute(): string
    {
        $status = $this->getStatusLabel();
        $usage = $this->getUsageCount();

        return sprintf(
            '%s (%s) - Usado en %d asignación(es)',
            $this->label,
            $status,
            $usage
        );
    }

    /**
     * Accessor para obtener información resumida.
     *
     * @return string
     */
    public function getSummaryAttribute(): string
    {
        return sprintf(
            '%s [%s]',
            $this->label,
            $this->isActive() ? 'ACTIVO' : 'INACTIVO'
        );
    }

    /**
     * Obtener tipos similares por descripción.
     *
     * @param int $limit Límite de resultados
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSimilarTypes(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        $words = explode(' ', $this->label);
        $searchTerm = implode('|', $words);

        return static::where('id', '!=', $this->id)
            ->where('label', 'REGEXP', $searchTerm)
            ->limit($limit)
            ->get();
    }

    /**
     * Determinar si se puede eliminar el tipo.
     *
     * @return bool
     */
    public function canBeDeleted(): bool
    {
        return !$this->isInUse();
    }
}
