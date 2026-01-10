<?php

namespace App\Models\Games\Shared\GameRoom;

use App\Models\Games\Game;
use App\Models\Games\Shared\GameMode\GameModes;
use App\Models\Games\Bingo\Rooms\BingoRoom;
use App\Models\Currencies\Currency;
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
}
