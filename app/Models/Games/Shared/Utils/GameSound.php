<?php

namespace App\Models\Games\Shared\Utils;

use App\Models\Games\Shared\Game;
use Illuminate\Database\Eloquent\Model;

class GameSound extends Model
{
    protected $table = "game_sounds";
    protected $fillable = ['price', 'description', 'game_id', 'status'];

    public function Game()
    {
        return $this->hasMany(Game::class, 'game_id');
    }
}
