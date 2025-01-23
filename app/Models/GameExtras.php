<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameExtras extends Model
{
    protected $table = "game_extras";
    protected $fillable = ['price', 'description', 'id_game', 'status'];

    public function Game()
    {
        return $this->hasMany(Games::class, 'id_game');
    }
}
