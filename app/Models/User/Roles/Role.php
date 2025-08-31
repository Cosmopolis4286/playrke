<?php

namespace App\Models\User\Roles;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Modelo Role
 *
 * Representa un rol/función que puede tener un usuario (Commerce, Seller, Admin, etc.)
 *
 * @property int $id
 * @property string $name
 * @property string|null $prefix
 * @property string|null $description
 * @property bool $status
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $users
 */
class Role extends Model
{
    protected $fillable = ['name', 'prefix', 'description', 'status'];

    /**
     * Usuarios asociados a este rol.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')
            ->withTimestamps();
    }
}
