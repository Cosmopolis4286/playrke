<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ghosts extends Model

{
    protected $table = "ghots";
    protected $fillable = ['id_game', 'room', 'ghosts', 'winners', 'awards'];

    public function Game()
    {
        return $this->belongsTo(Games::class, 'id_game');
    }
}
