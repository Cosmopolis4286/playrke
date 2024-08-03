<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoOffCtnsPayment extends Model
{
    protected $table = "bingo_off_ctns_payment";
    protected $fillable = [
        'operation', 'room', 'commerce', 'seller', 'player_link', 'payment_type',
        'coin', 'lot', 'amount', 'payment_proof', 'created_at', 'updated_at'
    ];

    public function TypeOpt()
    {
        return $this->belongsTo(TransactionType::class, 'operation');
    }

    public function Room()
    {
        return $this->belongsTo(BingoOffRoom::class, 'room');
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
        return $this->belongsTo(BingoOffCtnsLot::class, 'lot');
    }
}
