<?php

namespace App\Repositories\Games\Shared\GameMode;

use App\Models\Games\Shared\GameMode\GameModeAssignment;

class GameModeAssignmentRepository
{
    protected GameModeAssignment $model;

    public function __construct(GameModeAssignment $model)
    {
        $this->model = $model;
    }

    /**
     * Obtener todos los modos de juego asignados a un juego
     */
    public function getByGameId($gameId, $status = true)
    {
        return $this->model
            ->where('game_id', $gameId)
            ->where('status', $status)
            ->with('gameMode') // traer la información del modo de juego
            ->get();
    }

    /**
     * Crear una nueva asignación de modo de juego
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * Buscar una asignación específica
     */
    public function find($gameId, $gameModeId)
    {
        return $this->model
            ->where('game_id', $gameId)
            ->where('game_mode_id', $gameModeId)
            ->first();
    }
}
