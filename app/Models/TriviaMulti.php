<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TriviaMulti extends Model
{
    protected $table = "trivia_rooms_multi";
    protected $fillable = [
        'date',
        'time',
        'unix_date',
        'status',
        'price',
        'players',
        'all_sale',
        'jackpot',
        'room_commn',
        'advertising_image',
        'close_room',
        'code_uuid'
    ];

    public function Status()
    {
        return $this->belongsTo(StatusGame::class, 'status');
    }

    public function Questions()
    {
        return $this->hasMany(TriviaMultiQuest::class, 'room', 'id');
    }

    public function Cups()
    {
        return $this->hasMany(TriviaMultiCups::class, 'room', 'id');
    }

    public function PlayersRoom()
    {
        return $this->hasMany(TriviaMultiUser::class, 'room', 'id');
    }
}
