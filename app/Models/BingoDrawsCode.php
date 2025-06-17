<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoDrawsCode extends Model
{
    protected $table = 'bingo_draws_code';
    protected $fillable = ['num', 'sym', 'room'];   
    
    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }
}
