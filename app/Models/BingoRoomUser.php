<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoRoomUser extends Model
{
    protected $table = 'bingo_rooms_user';
    protected $fillable = ['room', 'provider', 'commerce', 'user', 'winner'];

    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }
}
