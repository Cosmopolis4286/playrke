<?php

namespace App\Models\Games\Common\PrizePool;

use App\Models\Game;
use App\Traits\HasActiveStatusScope;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tipo de pool de premios (Bote Fijo, Acumulativo, Mixto, Premio/Servicio),
 * administrable por admins. Cada tipo pertenece a un único juego (game_id)
 * — no se comparten tipos entre juegos.
 */
class PrizePoolType extends Model
{
    use HasActiveStatusScope, SnowflakeId;

    public const CODE_ACCUMULATIVE = 'ACCUMULATIVE';

    protected $casts = [
        'id' => 'string',
        'game_id' => 'string',
        'code' => 'string',
        'label' => 'string',
        'status' => 'string',
    ];

    protected $fillable = [
        'game_id',
        'code',
        'label',
        'status',
    ];

    public function game() : BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    /**
     * Bote acumulativo: se forma solo con las ventas, sin "Valor Fijo/Extra".
     * El resto de los tipos (FIXED/MIXED/PRIZE) exigen ese monto.
     */
    public function isAccumulative() : bool
    {
        return $this->code === self::CODE_ACCUMULATIVE;
    }
}
