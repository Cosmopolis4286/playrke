<?php

namespace App\Models;

use App\Models\Games\Common\Jackpot\JackpotType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Saldo actual acumulado de un jackpot (un pozo por jackpot_type_id, creado
 * bajo demanda al primer aporte). Análogo a Wallet — el historial de
 * movimientos vive en JackpotMovement.
 *
 * @property string $id
 * @property string $jackpot_type_id
 * @property float  $amount
 *
 * @property-read JackpotType $jackpotType
 */
class Jackpot extends Model
{
    use \App\Traits\SnowflakeId;

    protected $table = 'jackpots';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'jackpot_type_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function jackpotType(): BelongsTo
    {
        return $this->belongsTo(JackpotType::class, 'jackpot_type_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(JackpotMovement::class, 'jackpot_id');
    }
}
