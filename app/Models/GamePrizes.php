<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GamePrizes extends Model
{
    protected $table = "game_prizes";
    protected $fillable = ['description', 'game', 'status'];

    public function Game()
    {
        return $this->hasMany(Games::class, 'game');
    }

    public static function getValueByDescription($game, $description, $value = 'id')
    {
        return self::where('game', $game)->where('description', $description)->value($value);
    }
}
