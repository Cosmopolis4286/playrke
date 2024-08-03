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

    public function GameMode()
    {
        return $this->belongsTo(GameModes::class, 'game_mode');
    }

    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin');
    }

    public static function checkCoinGameRoom($coin, $room, $game, $gameMode = '')
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
}
