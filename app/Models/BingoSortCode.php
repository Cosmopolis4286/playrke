<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoSortCode extends Model
{
    protected $table = 'bingo_sort_code';
    protected $fillable = [
        'num', 'sym', 'room'
    ];

    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }
}
