<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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

    public static function createNewCoinInRoom($game, $room, $coin)
    {
        try {
            // Obtener el ID del juego
            $gameID = Games::getValueByName($game['name']);

            // Crear el nuevo registro
            self::create([
                'game' => $gameID,
                'game_mode' => $game['game_mode'],
                'room' => $room,
                'coin' => $coin,
            ]);
            return true;
        } catch (\Exception $e) {
            // Registrar error en el log si la creación falla
            Log::error('Error al crear nueva moneda en la sala: ' . $e->getMessage());
            return false;
        }
    }

    public static function existsCoinGameRoom($coin, $room, $game, $gameMode = '')
    {
        $gameID = Games::getValueByName($game);

        // Iniciar la consulta
        $query = self::where('coin', $coin)->where('room', $room)->where('game', $gameID);

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
