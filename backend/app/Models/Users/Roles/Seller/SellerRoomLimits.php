<?php

namespace App\Models\Users\Roles\Seller;

use App\Models\Games\Bingo\Rooms\BingoRoom;
use App\Models\Games\Shared\Game;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para límites de salas de vendedores.
 *
 * Representa los límites y comisiones asignados a vendedores específicos
 * para salas de juego particulares, gestionando el acceso y configuración
 * de comisiones por vendedor.
 */
class SellerRoomLimits extends Model
{
    use SnowflakeId;

    protected $table = "sellers_room_limits";

    protected $fillable = [
        'game',
        'room',
        'seller',
        'commission'
    ];

    protected $casts = [
        'commission' => 'float'
    ];

    /**
     * Relación con la sala de bingo.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }

    /**
     * Relación con el vendedor.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller');
    }

    /**
     * Relación con el juego.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game');
    }
}
