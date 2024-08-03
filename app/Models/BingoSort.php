<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoSort extends Model
{
    protected $table = 'bingo_sort';
    protected $fillable = [
        'chip', 'code', 'room', 'time', 'status'
    ];

    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }

    public function CodeSort()
    {
        return $this->belongsTo(BingoSortCode::class, 'room', 'room');
    }
}