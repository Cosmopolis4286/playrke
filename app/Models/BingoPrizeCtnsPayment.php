<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoPrizeCtnsPayment extends Model
{
    protected $table = "bingo_prize_ctns_payment";
    protected $fillable = [
        'prize_ctn', 'operation', 'room', 'provider', 'commerce', 'seller', 'player_link', 'sender',
        'prize_type', 'coin', 'lot', 'ctn', 'amount', 'payment_proof', 'created_at', 'updated_at'
    ];

    public function TypeOpt()
    {
        return $this->belongsTo(TransactionType::class, 'operation');
    }

    public function Prize()
    {
        return $this->belongsTo(GamePrizes::class, 'prize_type');
    }

    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin');
    }

    public function CtnLot()
    {
        return $this->belongsTo(BingoCtnsLot::class, 'lot');
    }

    public function Ctn()
    {
        return $this->belongsTo(BingoCtns::class, 'ctn');
    }
}
