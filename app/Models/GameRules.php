<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameRules extends Model
{
    protected $table = "game_rules";
    protected $fillable = ['user', 'game', 'rules', 'status'];

    public function Game()
    {
        return $this->belongsTo(Games::class, 'game');
    }

    public function User()
    {
        return $this->belongsTo(User::class, 'user');
    }

    public static function getValueByGame($game, $value = 'rules')
    {
        $gameID = is_string($game) ? Games::getValueByName($game) : $game;

        return self::where('game', $gameID)->value($value);
    }
}
