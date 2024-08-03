<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoCtns extends Model
{
    protected $table = 'bingo_ctns';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id', 'room', 'user', 'created_at',
        'b1', 'b2', 'b3', 'b4', 'b5',    'i1',    'i2',    'i3',    'i4',    'i5',
        'n1', 'n2', 'n3', 'n4', 'g1',    'g2',    'g3',    'g4',    'g5',
        'o1', 'o2', 'o3', 'o4', 'o5'
    ];

    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }

    public function User()
    {
        return $this->belongsTo(User::class, 'user');
    }
}
