<?php

namespace App\Models\Games\Shared\Utils;

use App\Models\Games\Shared\Games;
use Illuminate\Database\Eloquent\Model;

class GameSounds extends Model
{
    protected $table = "game_sounds";
    protected $fillable = ['price', 'description', 'game_id', 'status'];

    public function Game()
    {
        return $this->hasMany(Games::class, 'game_id');
    }
}
