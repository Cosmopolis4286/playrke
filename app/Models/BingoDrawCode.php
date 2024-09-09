<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoDrawCode extends Model
{
    protected $table = 'bingo_draw_code';
    protected $fillable = ['num', 'sym', 'room'];   
    
    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }
}
