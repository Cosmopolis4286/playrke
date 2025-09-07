<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameRoomJackpot extends Model
{
    protected $table = "game_room_jackpots";
    protected $fillable = ['game', 'room', 'currency', 'amount', 'status'];

    public function Game()
    {
        return $this->belongsTo(Games::class, 'game');
    }
}
