<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoUserRoom extends Model
{
    protected $table = 'bingo_room_user';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id', 'room', 'ctns', 'ctn_win', 'auto_mc', 'user', 'tm_stop', 'winner',
        'paid', 'bg_all', 'prize_sm', 'time_prize', 'chip_bing', 'dgn', 'fr_crns', 'sm_cross',
        'bg_cross', 'bg_line', 'created_at', 'updated_at'
    ];

    public function Seller()
    {
        return $this->belongsTo(Seller::class, 'seller');
    }

    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }
}
