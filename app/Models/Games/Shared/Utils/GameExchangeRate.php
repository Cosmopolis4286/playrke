<?php

namespace App\Models\Games\Shared\Utils;

use Illuminate\Database\Eloquent\Model;

class GameExchangeRate extends Model
{
    protected $table = "game_exchange_rate";
    protected $fillable = ['game', 'room', 'currency', 'exchange_rate'];

    public function Game()
    {
        return $this->belongsTo(Game::class, 'game');
    }

    public function Currency()
    {
        return $this->belongsTo(Currency::class, 'currency');
    }

    public static function getGameExchangeRateValue($gameName, $room, $currency, $value = 'exchange_rate')
    {
        $gameId = Game::getValueByName($gameName);

        return self::where('game', $gameId)->where('room', $room)->where('currency', $currency)->value($value);
    }

    public static function getCountGameExchangeRate($game, $room)
    {
        return self::where('game', $game)->where('room', $room)->count();
    }

    public static function existsGameExchangeRate($game, $room, $currency)
    {
        return self::where('game', $game)->where('room', $room)->where('currency', $currency)->exists();
    }
}
