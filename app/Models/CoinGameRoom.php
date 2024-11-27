<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoinGameRoom extends Model
{
    protected $table = "coins_game_rooms";
    protected $fillable = ['game', 'game_mode', 'room', 'coin'];

    public function Game()
    {
        return $this->belongsTo(Games::class, 'game');
    }

    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin');
    }

    public static function registerNewCoinInRoom($game, $room, $coin)
    {
        $gameID = Games::getValueByName($game['name']);

        self::create([
            'game' => $gameID,
            'game_mode' => $game['game_mode'],
            'room' => $room,
            'coin' => $coin
        ]);
    }

    public static function existsCoinGameRoom($coin, $room, $game, $gameMode = '')
    {
        // Iniciar la consulta
        $query = self::where('coin', $coin)->where('room', $room)->where('game', $game);

        // Añadir condicionalmente el filtro de 'game_mode'
        if ($gameMode) {
            $query->where('game_mode', $gameMode);
        }

        // Retornar el true/false de los resultados
        return $query->exists();
    }

    public static function getCoinsByRoom($game, $room, $gameMode)
    {
        // Iniciar la consulta
        $query = self::where('game', $game)->where('room', $room);

        // Añadir condicionalmente el filtro de 'game_mode'
        if ($gameMode) {
            $query->where('game_mode', $gameMode);
        }

        // Retornar el true/false de los resultados
        return $query->pluck('coin');
    }
}
