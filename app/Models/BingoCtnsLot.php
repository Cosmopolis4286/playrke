<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoCtnsLot extends Model
{
    protected $table = "bingo_ctns_lot";
    protected $fillable = [
        'room',
        'creator_user',
        'seller',
        'room_user',
        'player_user',
        'player_link',
        'ctn_price',
        'exchange_rate',
        'ctns',
        'coin',
        'sale_amount',
        'final_sale_amount',
        'status'
    ];

    // Relación con la sala
    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }

    // Relación con la configuracion de la sala
    public function RoomConfig()
    {
        return $this->belongsTo(BingoRoomConfig::class, 'room');
    }

    // Relación con la moneda
    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin');
    }

    // Relación con el enlace del jugador
    public function PlayerLink()
    {
        return $this->belongsTo(PlayerLink::class, 'player_link', 'id');
    }

    // Relación con los cartones
    public function Ctns()
    {
        return $this->hasMany(BingoCtns::class, 'lot', 'id');
    }

    // Relación con el vendedor
    public function Seller()
    {
        return $this->belongsTo(Seller::class, 'seller');
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
