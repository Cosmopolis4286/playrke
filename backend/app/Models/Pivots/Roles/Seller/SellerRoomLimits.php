<?php

namespace App\Models\Pivots\Roles\Seller;

use App\Models\Games\Bingo\Room\BingoRoom;
use App\Models\Game;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Modelo para límites de salas de vendedores.
 *
 * Representa los límites y comisiones asignados a vendedores específicos
 * para salas de juego particulares, gestionando el acceso y configuración
 * de comisiones por vendedor.
 */
class SellerRoomLimits extends Pivot
{
    use SnowflakeId;

    protected $table = "sellers_room_limits";

    protected $fillable = [
        'game_id',
        'room_id',
        'seller_id',
        'commission'
    ];

    protected $casts = [
        'game_id' => 'string',
        'room_id' => 'string',
        'seller_id' => 'string',
        'commission' => 'float'
    ];

    /**
     * Relación con la sala de bingo.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    /**
     * Relación con el vendedor.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    /**
     * Relación con el juego.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    /**
     * Obtener un valor específico de límite de sala por vendedor
     *
     * @param string $game Nombre del juego
     * @param string $sellerId ID del vendedor
     * @param string $roomId ID de la sala
     * @param string $field Campo a obtener
     * @return mixed
     */
    public static function getGameRoomValueBySeller(string $game, string $sellerId, string $roomId, string $field)
    {
        return static::where('game_id', $game)
            ->where('seller_id', $sellerId)
            ->where('room_id', $roomId)
            ->value($field);
    }
}
