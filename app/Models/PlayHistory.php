<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayHistory extends Model
{
    protected $table = "play_history";
    protected $fillable = ['date', 'time', 'game', 'room', 'coin', 'gain', 'action', 'user'];

    public function User()
    {
        return $this->belongsTo(Games::class, 'game');
    }
}
