<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoOffCtnsLot extends Model
{
    protected $table = "bingo_off_ctns_lot";
    protected $fillable = [
        'coin', 'exchange_rate', 'ctns', 'sale_seller_ctns',
        'room', 'provider', 'commerce', 'seller', 'player_link', 'status'
    ];

    // Relación con la habitación
    public function Room()
    {
        return $this->belongsTo(BingoOffRoom::class, 'room');
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
        return $this->hasMany(BingoOffCtns::class, 'lot', 'id');
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
        return $this->belongsTo(BingoOffCtnsPayment::class, 'id', 'lot');
    }
    
    public static function getCoinByIds($room)
    {
        return self::where('room', $room)->distinct('coin')->pluck('coin');
    }
}
