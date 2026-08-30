<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catálogo de misiones diarias — ver DailyMissionService para la lógica de
 * progreso/desbloqueo. Modelo delgado a propósito, sin lógica de negocio.
 *
 * @property string $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $requirement_type games_played | games_won
 * @property int $target
 * @property int $xp_reward
 * @property bool $is_active
 */
class DailyMission extends Model
{
    use \App\Traits\SnowflakeId;

    protected $table = 'daily_missions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public const REQUIREMENT_GAMES_PLAYED = 'games_played';
    public const REQUIREMENT_GAMES_WON    = 'games_won';

    protected $fillable = [
        'code',
        'name',
        'description',
        'requirement_type',
        'target',
        'xp_reward',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'target'     => 'integer',
        'xp_reward'  => 'integer',
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function progress(): HasMany
    {
        return $this->hasMany(UserDailyMissionProgress::class, 'daily_mission_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
