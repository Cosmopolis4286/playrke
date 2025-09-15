<?php

namespace App\Models\Users\Roles\Company;

use App\Models\Users\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
    use SnowflakeId;

    protected $fillable = ['name', 'slug'];

    /**
     * Usuarios asociados a esta empresa
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role_id')
            ->withTimestamps();
    }
}
