<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistGame extends Model
{
    protected $table = "hist_games";
    protected $fillable = [
        'id', 'date', 'time', 'game', 'room', 'coin', 'gain',
        'user', 'created_at', 'updated_at'
    ];

    public function User()
    {
        return $this->belongsTo(Games::class, 'game');
    }
}
