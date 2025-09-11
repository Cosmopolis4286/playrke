<?php

namespace App\Models;

use App\Models\Games\Bingo\Rooms\BingoRoom;
use Illuminate\Database\Eloquent\Model;

class BingoDrawCode extends Model
{
    protected $table = 'bingo_draws_code';
    protected $fillable = ['num', 'sym', 'room'];

    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }
}
