<?php

namespace App\Models\Games\Common\Jackpot;

use App\Models\Currency;
use App\Models\Game;
use App\Models\Jackpot;
use App\Traits\HasActiveStatusScope;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Tipo de jackpot (ej. "Jackpot Semanal USD"), administrable por admins.
 * Cada tipo pertenece a un único juego y a una única moneda — un jackpot
 * semanal en USD y otro en Bs son pozos distintos, cada uno con su propio
 * saldo en `jackpots`.
 */
class JackpotType extends Model
{
    use SnowflakeId, HasActiveStatusScope;

    public const PERIODICITY_PER_ROOM = 'PER_ROOM';
    public const PERIODICITY_WEEKLY = 'WEEKLY';
    public const PERIODICITY_BIWEEKLY = 'BIWEEKLY';
    public const PERIODICITY_MONTHLY = 'MONTHLY';

    protected $fillable = [
        'game_id',
        'currency_id',
        'code',
        'label',
        'periodicity',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'game_id' => 'string',
        'currency_id' => 'string',
        'code' => 'string',
        'label' => 'string',
        'periodicity' => 'string',
        'status' => 'string',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function jackpot(): HasOne
    {
        return $this->hasOne(Jackpot::class, 'jackpot_type_id');
    }
}
