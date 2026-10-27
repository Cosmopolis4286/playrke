<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Reputación cacheada del jugador (0-100). Ver ReputationService para el
 * cálculo; este modelo es solo almacenamiento, sin lógica de negocio.
 *
 * @property string              $id
 * @property string              $user_id
 * @property int                 $score
 * @property \Carbon\Carbon|null $calculated_at
 */
class UserReputation extends Model
{
    use \App\Traits\SnowflakeId;

    public const DEFAULT_SCORE = 100;
    public $incrementing = false;

    protected $casts = [
        'score' => 'integer',
        'calculated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'score',
        'calculated_at',
    ];
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $table = 'user_reputations';

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
