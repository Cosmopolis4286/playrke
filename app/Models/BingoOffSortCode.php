<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoOffSortCode extends Model
{
    protected $table = 'bingo_off_sort_code';
    protected $fillable = [
        'num', 'sym', 'room'
    ];

    public function Room()
    {
        return $this->belongsTo(BingoOffRoom::class, 'room');
    }
}
