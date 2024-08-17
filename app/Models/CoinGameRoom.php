<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoinGameRoom extends Model
{
    protected $table = "coins_game_rooms";
    protected $fillable = ['game', 'room', 'coin'];

    public function Game()
    {
        return $this->belongsTo(Games::class, 'game');
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
