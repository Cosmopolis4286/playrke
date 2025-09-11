<?php

namespace App\Repositories\Games\Shared\GameMode;

use App\Models\Games\Shared\GameMode\GameMode;

class GameModeRepository
{
    protected GameMode $model;

    public function __construct(GameMode $model)
    {
        $this->model = $model;
    }

    /**
     * Buscar un modo de juego por descripción
     */
    public function findByDescription(string $description): ?GameMode
    {
        return $this->model->where('description', $description)->first();
    }

    /**
     * Crear un nuevo modo de juego
     */
    public function create(array $data): GameMode
    {
        return $this->model->create($data);
    }

    /**
     * Listar todos los modos de juego activos
     */
    public function allActive()
    {
        return $this->model->where('is_active', true)->get();
    }

    /**
     * Buscar por ID
     */
    public function findById($id): ?GameMode
    {
        return $this->model->find($id);
    }
}
