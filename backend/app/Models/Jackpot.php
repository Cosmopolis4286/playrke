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
 * @property string      $id
 * @property string      $jackpot_type_id
 * @property float       $amount
 * @property JackpotType $jackpotType
 */
class Jackpot extends Model
{
    use \App\Traits\SnowflakeId;
    public $incrementing = false;

    protected $casts = [
        'amount' => 'float',
    ];

    protected $fillable = [
        'jackpot_type_id',
        'amount',
    ];
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $table = 'jackpots';

    public function jackpotType() : BelongsTo
    {
        return $this->belongsTo(JackpotType::class, 'jackpot_type_id');
    }

    public function movements() : HasMany
    {
        return $this->hasMany(JackpotMovement::class, 'jackpot_id');
    }
}
