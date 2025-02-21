<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoCtnsPayment extends Model
{
    protected $table = "bingo_ctns_payment";
    protected $fillable = [
        'operation',
        'room',
        'commerce',
        'subsidiary',
        'seller',
        'player_link',
        'payment_type',
        'coin',
        'lot',
        'amount',
        'payment_proof',
        'created_at',
        'updated_at'
    ];

    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }

    public function Commerce()
    {
        return $this->belongsTo(Commerce::class, 'commerce');
    }

    public function Seller()
    {
        return $this->belongsTo(Seller::class, 'seller');
    }

    public function PlayerLink()
    {
        return $this->belongsTo(PlayerLink::class, 'player_link');
    }

    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin');
    }

    public function CtnLot()
    {
        return $this->belongsTo(BingoCtnsLot::class, 'lot');
    }
}
