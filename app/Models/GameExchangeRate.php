<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameExchangeRate extends Model
{
    protected $table = "game_exchange_rate";
    protected $fillable = ['game', 'room', 'coin', 'exchange_rate'];

    public function Game()
    {
        return $this->belongsTo(Games::class, 'game');
    }

    public function Commerce() // La voy a necesitar en un futuro
    {
        return $this->belongsTo(Commerce::class, 'commerce');
    }

    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin');
    }

    public static function getGameExchangeRateValue($game, $room, $coin, $value = 'exchange_rate')
    {
        return self::where('game', $game)->where('room', $room)->where('coin', $coin)->value($value);
    }

    public static function getCountGameExchangeRate($game, $room)
    {
        return self::where('game', $game)->where('room', $room)->count();
    }

    public static function existsGameExchangeRate($game, $room, $coin)
    {
        return self::where('game', $game)->where('room', $room)->where('coin', $coin)->exists();
    }
}
