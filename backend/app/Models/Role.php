<?php

namespace App\Models;

use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasPermissions;

/**
 * Modelo para la gestión de roles de usuario.
 *
 * Representa los diferentes roles que pueden tener los usuarios en el sistema,
 * con información de nombre, prefijo, descripción y estado.
 */
class Role extends Model
{
    use SnowflakeId, HasPermissions;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'roles';

    /**
     * Los campos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',         // Nombre del rol
        'prefix',       // Prefijo del rol (para categorización)
        'description',  // Descripción del rol
        'status',       // Estado del rol (activo/inactivo)
    ];

    /**
     * Los atributos que deben ser casteados a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Relación con los usuarios que tienen este rol.
     *
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * Scope para obtener roles activos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope para obtener roles inactivos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('status', false);
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
     * Scope para buscar por nombre.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $name
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('name', 'LIKE', "%{$name}%");
    }

    /**
     * Scope para filtrar por prefijo.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $prefix
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPrefix($query, string $prefix)
    {
        return $query->where('prefix', $prefix);
    }

    /**
     * Scope para ordenar por nombre.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction Dirección del ordenamiento (asc|desc)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByName($query, string $direction = 'asc')
    {
        return $query->orderBy('name', $direction);
    }

    /**
     * Verificar si el rol está activo.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === true;
    }

    /**
     * Verificar si el rol está inactivo.
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        return $this->status === false;
    }

    /**
     * Activar el rol.
     *
     * @return bool
     */
    public function activate(): bool
    {
        return $this->update(['status' => true]);
    }

    /**
     * Desactivar el rol.
     *
     * @return bool
     */
    public function deactivate(): bool
    {
        return $this->update(['status' => false]);
    }

    /**
     * Obtener el nombre normalizado del rol.
     *
     * @return string
     */
    public function getNormalizedName(): string
    {
        return Str::title(trim($this->name));
    }

    /**
     * Obtener información del estado del rol.
     *
     * @return string
     */
    public function getStatusLabel(): string
    {
        return $this->isActive() ? 'Activo' : 'Inactivo';
    }

    /**
     * Verificar si el rol está siendo utilizado por algún usuario.
     *
     * @return bool
     */
    public function isInUse(): bool
    {
        return $this->users()->exists();
    }

    /**
     * Obtener el número de usuarios que tienen este rol.
     *
     * @return int
     */
    public function getUsersCount(): int
    {
        return $this->users()->count();
    }

    /**
     * Verificar si el rol tiene un prefijo específico.
     *
     * @param string $prefix Prefijo a verificar
     * @return bool
     */
    public function hasPrefix(string $prefix): bool
    {
        return $this->prefix === strtoupper($prefix);
    }

    /**
     * Verificar si el rol tiene descripción.
     *
     * @return bool
     */
    public function hasDescription(): bool
    {
        return !empty($this->description);
    }

    /**
     * Obtener el prefijo formateado.
     *
     * @return string
     */
    public function getFormattedPrefix(): string
    {
        return $this->prefix ? "[{$this->prefix}]" : '';
    }

    /**
     * Obtener el nombre completo del rol (con prefijo si existe).
     *
     * @return string
     */
    public function getFullName(): string
    {
        $prefix = $this->getFormattedPrefix();
        return $prefix ? "{$prefix} {$this->name}" : $this->name;
    }

    /**
     * Verificar si el nombre del rol es válido.
     *
     * @param string $name Nombre a validar
     * @return bool
     */
    public static function isValidName(string $name): bool
    {
        $trimmed = trim($name);

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
     * Normalizar un nombre de rol.
     *
     * @param string $name Nombre a normalizar
     * @return string Nombre normalizado
     */
    public static function normalizeName(string $name): string
    {
        // Limpiar espacios extra
        $normalized = trim($name);

        // Convertir a título
        $normalized = Str::title($normalized);

        // Reemplazar múltiples espacios con uno solo
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return $normalized;
    }

    /**
     * Mutator para normalizar el nombre antes de guardar.
     *
     * @param string $value
     * @return void
     */
    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = self::normalizeName($value);
    }

    /**
     * Mutator para normalizar el prefijo antes de guardar.
     *
     * @param string|null $value
     * @return void
     */
    public function setPrefixAttribute(?string $value): void
    {
        $this->attributes['prefix'] = $value ? strtoupper(trim($value)) : null;
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
     * Accessor para obtener información completa del rol.
     *
     * @return string
     */
    public function getFullInfoAttribute(): string
    {
        $status = $this->getStatusLabel();
        $userCount = $this->getUsersCount();
        $fullName = $this->getFullName();

        return sprintf(
            '%s (%s) - %d usuario(s)',
            $fullName,
            $status,
            $userCount
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
            $this->getFullName(),
            $this->isActive() ? 'ACTIVO' : 'INACTIVO'
        );
    }

    /**
     * Determinar si se puede eliminar el rol.
     *
     * @return bool
     */
    public function canBeDeleted(): bool
    {
        return !$this->isInUse();
    }
}
