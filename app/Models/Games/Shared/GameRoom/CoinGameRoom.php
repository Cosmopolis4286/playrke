<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class CurrencyGameRoom extends Model
{
    protected $table = "currencies_game_rooms";
    protected $fillable = ['game', 'game_mode', 'room', 'currency'];

    public function Game()
    {
        return $this->belongsTo(Game::class, 'game');
    }

    public static function createNewCurrencyInRoom($game, $room, $currency)
    {
        try {
            // Obtener el ID del juego
            $gameId = Game::getValueByName($game['name']);

            // Crear el nuevo registro
            self::create([
                'game' => $gameId,
                'game_mode' => $game['game_mode'],
                'room' => $room,
                'currency' => $currency,
            ]);
            return true;
        } catch (\Exception $e) {
            // Registrar error en el log si la creación falla
            Log::error('Error al crear nueva moneda en la sala: ' . $e->getMessage());
            return false;
        }
    }

    public static function existsCurrencyGameRoom($currency, $room, $game, $gameMode = '')
    {
        $gameId = Game::getValueByName($game);

        // Iniciar la consulta
        $query = self::where('currency', $currency)->where('room', $room)->where('game', $gameId);

        // Añadir condicionalmente el filtro de 'game_mode'
        if ($gameMode) {
            $query->where('game_mode', $gameMode);
        }

        // Retornar el true/false de los resultados
        return $query->exists();
    }

    public static function getCurrenciesByRoom($game, $room, $gameMode = null)
    {
        // Obtener el ID del juego si se pasa como string
        $gameId = is_string($game) ? Game::getValueByName($game) : $game;

        // Construir la consulta
        $query = self::select('currency')->where('game', $gameId)->where('room', $room);

        // Agregar filtro de modo de juego si se proporciona
        if (!is_null($gameMode)) {
            $query->where('game_mode', $gameMode);
        }

        // Retornar monedas distintas disponibles en esa sala y modo
        return $query->distinct()->pluck('currency');
    }
}
