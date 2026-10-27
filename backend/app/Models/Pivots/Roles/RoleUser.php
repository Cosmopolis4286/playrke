<?php

namespace App\Models\Pivots\Roles;

use App\Casts\AsActiveStatusEnum;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Modelo pivot para la relación User ↔ Role
 *
 * Representa la tabla `role_user`. Se usa cuando la relación
 * tiene atributos adicionales o lógica propia.
 *
 * @property int                             $user_id
 * @property int                             $role_id
 * @property int|null                        $assigned_by ID del usuario que asignó el rol
 * @property \Illuminate\Support\Carbon|null $expires_at  Fecha de expiración del rol
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class RoleUser extends Pivot
{
    /**
     * Los atributos que deben ser casteados a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => AsActiveStatusEnum::class,
        'expires_at' => 'datetime',
    ];

    /**
     * Campos asignables
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'role_id',
        'assigned_by',
        'expires_at',
    ];
    /**
     * Tabla asociada
     *
     * @var string
     */
    protected $table = 'role_user';

    /**
     * Obtener el usuario que asignó este rol
     *
     * @return BelongsTo<User, RoleUser>
     */
    public function assignedBy() : BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Obtener el rol asociado a este usuario
     *
     * @return BelongsTo<Role, RoleUser>
     */
    public function role() : BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Obtener el usuario asociado a este rol
     *
     * @return BelongsTo<User, RoleUser>
     */
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
