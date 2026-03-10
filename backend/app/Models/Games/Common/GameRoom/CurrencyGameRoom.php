<?php

namespace App\Models\Games\Common\GameRoom;

use App\Models\Game;
use App\Models\Games\Common\GameMode\GameModes;
use App\Models\Games\Bingo\Room\BingoRoom;
use App\Models\Currency;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para monedas disponibles en salas de juego
 */
class CurrencyGameRoom extends Model
{
    use SnowflakeId;

    protected $table = 'currencies_game_rooms';

    protected $fillable = [
        'game_id',
        'game_mode_id',
        'room_id',
        'currency_id'
    ];

    /**
     * Relación con el juego
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    /**
     * Relación con el modo de juego
     */
    public function gameMode(): BelongsTo
    {
        return $this->belongsTo(GameModes::class, 'game_mode_id');
    }

    /**
     * Relación con la sala
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    /**
     * Relación con la moneda
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Verificar si existe una moneda configurada para una sala
     */
    public static function existsCurrencyGameRoom(string $currencyId, string $roomId, string $gameName, $gameMode): bool
    {
        return static::where('currency_id', $currencyId)
            ->where('room_id', $roomId)
            ->whereHas('game', function($query) use ($gameName) {
                $query->where('name', $gameName);
            })
            ->where('game_mode_id', $gameMode) // Usar ID directamente
            ->exists();
    }

    /**
     * Crear nueva configuración de moneda en sala
     */
    public static function createNewCurrencyInRoom(array $game, string $roomId, string $currencyId): self
    {
        // Obtener ID del juego
        $gameModel = \App\Models\Game::where('name', $game['name'])->first();

        return static::create([
            'game_id' => $gameModel?->id,
            'game_mode_id' => $game['game_mode'], // Usar ID directamente
            'room_id' => $roomId,
            'currency_id' => $currencyId
        ]);
    }
}
