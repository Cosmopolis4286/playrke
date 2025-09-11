<?php

namespace App\Models\Users\Roles\Company;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Company model
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property \Illuminate\Database\Eloquent\Collection $users
 */
class Company extends Model
{
    protected $fillable = ['name', 'slug'];

    /**
     * Usuarios asociados a esta empresa
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role_id')
            ->withTimestamps();
    }
}
