<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoCtnsLot extends Model
{
    protected $table = "bingo_ctns_lot";
    protected $fillable = [
        'room',
        'customer',
        'commerce',
        'subsidiary',
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

    // Relación con la habitación
    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }

    // Relación con la moneda
    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin');
    }

    // Relación con el enlace del cliente
    public function Customer()
    {
        return $this->belongsTo(Customer::class, 'customer', 'id');
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
        return self::where('room', $room)->distinct('coin')->pluck('coin');
    }
}
