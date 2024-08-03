<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TriviaMulti extends Model
{

    protected $table = "triv_multi";
    protected $fillable = [
        'id', 'nombre', 'status', 'coin', 'precio', 'pj', 'min_pj', 'type_boat',
        'jackpot', 'com', 'tickets', 'fecha', 'hora', 'replay', 'imagen', 'jackpot_f'
    ];

    public function Status()
    {
        return $this->belongsTo(StatusGame::class, 'status');
    }

    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin');
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
        return $this->hasMany(TriviaMultiUserRoom::class, 'room', 'id');
    }
}
