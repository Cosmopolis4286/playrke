<?php

namespace App\Models;

use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Contracts\Permission as PermissionContract;
use Spatie\Permission\Exceptions\RoleAlreadyExists;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Guard;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\RefreshesPermissionCache;

/**
 * Modelo para la gestión de roles de usuario.
 *
 * Representa los diferentes roles que pueden tener los usuarios en el sistema,
 * con información de nombre, prefijo, descripción y estado.
 * Implementa el contrato de Spatie para compatibilidad con laravel-permission.
 */
class Role extends Model implements RoleContract
{
    use SnowflakeId, HasPermissions, RefreshesPermissionCache;

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
        'id'     => 'string',
        'status' => 'boolean',
    ];


    // =========================================================================
    // Implementación del contrato Spatie\Permission\Contracts\Role
    // =========================================================================

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.permission', Permission::class),
            config('permission.table_names.role_has_permissions'),
            'role_id',
            'permission_id'
        );
    }

    public static function create(array $attributes = [])
    {
        $attributes['guard_name'] = $attributes['guard_name'] ?? Guard::getDefaultName(static::class);

        $role = static::where('name', $attributes['name'])
            ->where('guard_name', $attributes['guard_name'])
            ->first();

        if ($role) {
            throw RoleAlreadyExists::create($attributes['name'], $attributes['guard_name']);
        }

        return static::query()->create($attributes);
    }

    public static function findByName(string $name, ?string $guardName = null): RoleContract
    {
        $guardName = $guardName ?? Guard::getDefaultName(static::class);

        $role = static::where('name', $name)
            ->where('guard_name', $guardName)
            ->first();

        if (! $role) {
            throw RoleDoesNotExist::named($name);
        }

        return $role;
    }

    public static function findById(int|string $id, ?string $guardName = null): RoleContract
    {
        $guardName = $guardName ?? Guard::getDefaultName(static::class);

        $role = static::where((new static)->getKeyName(), (string) $id)
            ->where('guard_name', $guardName)
            ->first();

        if (! $role) {
            throw RoleDoesNotExist::withId($id);
        }

        return $role;
    }

    public static function findOrCreate(string $name, ?string $guardName = null): RoleContract
    {
        $guardName = $guardName ?? Guard::getDefaultName(static::class);

        $role = static::where('name', $name)
            ->where('guard_name', $guardName)
            ->first();

        if (! $role) {
            return static::query()->create(['name' => $name, 'guard_name' => $guardName]);
        }

        return $role;
    }

    public function hasPermissionTo($permission, ?string $guardName = null): bool
    {
        if (is_string($permission)) {
            $permission = app(PermissionContract::class)->findByName($permission, $guardName ?? $this->attributes['guard_name'] ?? Guard::getDefaultName(static::class));
        }

        if (is_int($permission) || is_numeric($permission)) {
            $permission = app(PermissionContract::class)->findById($permission, $guardName ?? $this->attributes['guard_name'] ?? Guard::getDefaultName(static::class));
        }

        return $this->permissions->contains($permission);
    }

    // =========================================================================
    // Relaciones con usuarios
    // =========================================================================

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
