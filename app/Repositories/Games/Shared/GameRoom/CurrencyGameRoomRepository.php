<?php

namespace App\Repositories\Games\Shared\GameRoom;

use App\Models\Games\Shared\GameRoom\CurrencyGameRoom;
use App\Repositories\BaseRepository;
use App\Repositories\Games\Shared\GameRoom\Contracts\CurrencyGameRoomRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Repository para manejar operaciones de monedas en salas de juego
 */
class CurrencyGameRoomRepository extends BaseRepository implements CurrencyGameRoomRepositoryContract
{
    /**
     * Constructor del repository
     *
     * @param CurrencyGameRoom $model
     */
    public function __construct(CurrencyGameRoom $model)
    {
        parent::__construct($model);
    }

    /**
     * Crea nueva moneda en sala
     *
     * @param array $game Datos del juego
     * @param mixed $roomId ID de la sala
     * @param mixed $currencyId ID de la moneda
     * @return bool
     */
    public function createNewCurrencyInRoom(array $game, $roomId, $currencyId): bool
    {
        try {
            $this->model->create([
                'game_id' => $game['game_id'],
                'game_mode_id' => $game['game_mode_id'] ?? null,
                'room_id' => $roomId,
                'currency_id' => $currencyId,
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Error al crear nueva moneda en la sala: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si existe combinación de moneda, sala, juego y modo
     *
     * @param mixed $currencyId ID de la moneda
     * @param mixed $roomId ID de la sala
     * @param mixed $gameId ID del juego
     * @param mixed|null $gameModeId ID del modo de juego
     * @return bool
     */
    public function existsCurrencyGameRoom($currencyId, $roomId, $gameId, $gameModeId = null): bool
    {
        $query = $this->model->where('currency_id', $currencyId)
            ->where('room_id', $roomId)
            ->where('game_id', $gameId);

        if ($gameModeId !== null) {
            $query->where('game_mode_id', $gameModeId);
        }

        return $query->exists();
    }

    /**
     * Obtiene monedas disponibles por sala
     *
     * @param mixed $gameId ID del juego
     * @param mixed $roomId ID de la sala
     * @param mixed|null $gameModeId ID del modo de juego
     * @return Collection
     */
    public function getCurrenciesByRoom($gameId, $roomId, $gameModeId = null): Collection
    {
        $query = $this->model->select('currency_id')
            ->where('game_id', $gameId)
            ->where('room_id', $roomId);

        if ($gameModeId !== null) {
            $query->where('game_mode_id', $gameModeId);
        }

        return $query->distinct()->get();
    }

    /**
     * Obtiene configuraciones por juego
     *
     * @param mixed $gameId ID del juego
     * @return Collection
     */
    public function getByGame($gameId): Collection
    {
        return $this->model->where('game_id', $gameId)->get();
    }

    /**
     * Obtiene configuraciones por sala
     *
     * @param mixed $roomId ID de la sala
     * @return Collection
     */
    public function getByRoom($roomId): Collection
    {
        return $this->model->where('room_id', $roomId)->get();
    }

    /**
     * Obtiene configuraciones por moneda
     *
     * @param mixed $currencyId ID de la moneda
     * @return Collection
     */
    public function getByCurrency($currencyId): Collection
    {
        return $this->model->where('currency_id', $currencyId)->get();
    }

    /**
     * Obtiene configuraciones por modo de juego
     *
     * @param mixed $gameModeId ID del modo de juego
     * @return Collection
     */
    public function getByGameMode($gameModeId): Collection
    {
        return $this->model->where('game_mode_id', $gameModeId)->get();
    }

    /**
     * Elimina configuración específica
     *
     * @param mixed $gameId ID del juego
     * @param mixed $roomId ID de la sala
     * @param mixed $currencyId ID de la moneda
     * @param mixed|null $gameModeId ID del modo de juego
     * @return bool
     */
    public function removeConfiguration($gameId, $roomId, $currencyId, $gameModeId = null): bool
    {
        $query = $this->model->where('game_id', $gameId)
            ->where('room_id', $roomId)
            ->where('currency_id', $currencyId);

        if ($gameModeId !== null) {
            $query->where('game_mode_id', $gameModeId);
        }

        return $query->delete() > 0;
    }

    /**
     * Obtiene salas que soportan una moneda específica
     *
     * @param mixed $currencyId ID de la moneda
     * @param mixed $gameId ID del juego
     * @return Collection
     */
    public function getRoomsByCurrency($currencyId, $gameId): Collection
    {
        return $this->model->select('room_id')
            ->where('currency_id', $currencyId)
            ->where('game_id', $gameId)
            ->distinct()
            ->get();
    }
}