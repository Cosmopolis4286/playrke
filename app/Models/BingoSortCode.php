<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoSortCode extends Model
{
    protected $table = 'bingo_sort_code';
    protected $fillable = ['num', 'sym', 'game_mode', 'room'];

    public function GameMode()
    {
        return $this->belongsTo(GameModes::class, 'game_mode', 'game_mode');
    }
    
    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }
}
