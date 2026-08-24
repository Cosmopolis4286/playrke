<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Reputación cacheada del jugador (0-100). Ver ReputationService para el
 * cálculo; este modelo es solo almacenamiento, sin lógica de negocio.
 *
 * @property string $id
 * @property string $user_id
 * @property int $score
 * @property \Carbon\Carbon|null $calculated_at
 */
class UserReputation extends Model
{
    use \App\Traits\SnowflakeId;

    protected $table = 'user_reputations';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public const DEFAULT_SCORE = 100;

    protected $fillable = [
        'user_id',
        'score',
        'calculated_at',
    ];

    protected $casts = [
        'score'         => 'integer',
        'calculated_at' => 'datetime',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
