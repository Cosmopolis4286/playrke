<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoPrizeCtnsPayment extends Model
{
    protected $table = "bingo_prize_ctns_payment";
    protected $fillable = [
        'prize_ctn',
        'payment',
        'room',
        'creator_user_id',
        'seller',
        'player_link',
        'user',
        'coin',
        'ctn',
        'amount',
        'payment_proof',
        'created_at',
        'updated_at'
    ];

    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin');
    }

    public function Ctn()
    {
        return $this->belongsTo(BingoCtns::class, 'ctn');
    }
}
