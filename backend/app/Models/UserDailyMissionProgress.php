<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Progreso de un usuario en una misión diaria, para un día calendario
 * (UTC) específico. Ver DailyMissionService para la lógica de
 * incremento/completado.
 *
 * @property string              $id
 * @property string              $user_id
 * @property string              $daily_mission_id
 * @property \Carbon\Carbon      $date
 * @property int                 $progress
 * @property \Carbon\Carbon|null $completed_at
 */
class UserDailyMissionProgress extends Model
{
    use \App\Traits\SnowflakeId;
    public $incrementing = false;

    protected $casts = [
        'date' => 'date',
        'progress' => 'integer',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'daily_mission_id',
        'date',
        'progress',
        'completed_at',
    ];
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $table = 'user_daily_mission_progress';

    public function isCompleted() : bool
    {
        return $this->completed_at !== null;
    }

    public function mission() : BelongsTo
    {
        return $this->belongsTo(DailyMission::class, 'daily_mission_id');
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
