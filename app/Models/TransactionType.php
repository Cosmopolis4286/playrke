<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionType extends Model
{
    protected $table = 'transaction_type';
    protected $fillable = [
        'id', 'description', 'room', 'time', 'status'
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