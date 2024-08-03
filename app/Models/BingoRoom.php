<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoRoom extends Model
{
    protected $table = "bingo_room";
    protected $fillable = [
        'id', 'img', 'name', 'status', 'date', 'time', 'replay', 'coin', 'price', 'val_automc',
        'val_songs', 'players', 'jackpot', 'type_boat', 'com', 'profit', 'ctns', 'encrypted'
    ];

    public function Status()
    {
        return $this->belongsTo(StatusGame::class, 'status');
    }

    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin');
    }

    public function PlayersRoom()
    {
        return $this->hasMany(BingoUserRoom::class, 'room', 'id');
    }
}
