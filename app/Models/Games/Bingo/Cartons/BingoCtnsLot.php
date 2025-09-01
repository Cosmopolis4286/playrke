<?php

namespace App\Models\Games\Bingo\Cartons;

use App\Models\Coins\Coin;
use App\Models\Games\Bingo\Rooms\BingoRoom;
use App\Models\User\Roles\Player\External\ExternalPlayers;
use App\Models\User\Roles\Seller\Seller;
use Illuminate\Database\Eloquent\Model;

class BingoCtnsLot extends Model
{
    protected $table = "bingo_ctns_lot";
    protected $fillable = [
        'room_id',
        'creator_user_id',
        'seller_id',
        'player_user',
        'room_user',
        'ctns',
        'coin_id',
        'ctn_price',
        'exchange_rate',
        'sale_amount',
        'seller_commn',
        'status'
    ];

    // Relación con la sala
    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    // Relación con la configuracion de la sala
    public function RoomConfig()
    {
        return $this->belongsTo(BingoRoomConfig::class, 'room');
    }

    // Relación con la moneda
    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin_id');
    }

    // Relación con el enlace del jugador
    public function ExternalPlayers()
    {
        return $this->belongsTo(ExternalPlayers::class, 'room_user', 'room_user');
    }

    // Relación con los cartones
    public function Ctns()
    {
        return $this->hasMany(BingoCtns::class, 'lot_id');
    }

    // Relación con el vendedor
    public function Seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    // Relación con la sala de venta del vendedor
    public function ComSellerRoom()
    {
        return $this->belongsTo(SellerRoomLimits::class, 'seller', 'seller');
    }

    // Relación con el pago de cartones
    public function Payment()
    {
        return $this->belongsTo(BingoCtnsPayment::class, 'id', 'lot');
    }

    public static function getCoinByIds($room)
    {
        return self::select('coin')->where('room', $room)->distinct()->pluck('coin');
    }
}
