<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameSounds extends Model
{
    protected $table = "game_sounds";
    protected $fillable = ['price', 'description', 'game', 'status'];

    public function Game()
    {
        return $this->hasMany(Games::class, 'game');
    }
}
