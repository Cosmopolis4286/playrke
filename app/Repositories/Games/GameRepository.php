<?php

namespace App\Repositories\Games;

use App\Models\Games\Shared\Game;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Games\Contracts\GameRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Repositorio para operaciones con juegos
 * Maneja todas las consultas complejas relacionadas con Game
 */
class GameRepository extends BaseRepository implements GameRepositoryContract
{
    public function __construct(Game $model)
    {
        parent::__construct($model);
    }

    /**
     * Buscar juego por nombre
     *
     * @param string $name Nombre del juego
     * @param array $columns Columnas a seleccionar
     * @return Game|null
     */
    public function findByName(string $name, array $columns = ['*']): ?Game
    {
        return $this->model->where('name', strtoupper($name))->first($columns);
    }

    /**
     * Obtener juegos por estado
     *
     * @param string $status Estado del juego (ACTIVE/INACTIVE/MAINTENANCE)
     * @param array $columns Columnas a seleccionar
     * @return Collection<Game>
     */
    public function getByStatus(string $status, array $columns = ['*']): Collection
    {
        return $this->model->where('status', strtoupper($status))
            ->orderBy('name')
            ->get($columns);
    }

    /**
     * Obtener juegos activos
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Game>
     */
    public function getActive(array $columns = ['*']): Collection
    {
        return $this->getByStatus('ACTIVE', $columns);
    }

    /**
     * Obtener juegos inactivos
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Game>
     */
    public function getInactive(array $columns = ['*']): Collection
    {
        return $this->getByStatus('INACTIVE', $columns);
    }

    /**
     * Obtener juegos en mantenimiento
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Game>
     */
    public function getInMaintenance(array $columns = ['*']): Collection
    {
        return $this->getByStatus('MAINTENANCE', $columns);
    }

    /**
     * Buscar juegos por nombre parcial
     *
     * @param string $searchTerm Término de búsqueda
     * @param array $columns Columnas a seleccionar
     * @return Collection<Game>
     */
    public function searchByName(string $searchTerm, array $columns = ['*']): Collection
    {
        return $this->model->where('name', 'like', "%{$searchTerm}%")
            ->orderBy('name')
            ->get($columns);
    }

    /**
     * Verificar si existe un juego por nombre
     *
     * @param string $name Nombre del juego
     * @return bool
     */
    public function existsByName(string $name): bool
    {
        return $this->model->where('name', strtoupper($name))->exists();
    }

    /**
     * Obtener juegos ordenados por nombre
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Game>
     */
    public function getAllOrderedByName(array $columns = ['*']): Collection
    {
        return $this->model->orderBy('name')->get($columns);
    }

    /**
     * Obtener valor específico de un juego por nombre
     *
     * @param string $name Nombre del juego
     * @param string $column Columna a obtener
     * @return mixed
     */
    public function getValueByName(string $name, string $column): mixed
    {
        return $this->model->where('name', strtoupper($name))->value($column);
    }

    /**
     * Obtener juegos disponibles (método legacy)
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Game>
     */
    public function getAvailableGames(array $columns = ['*']): Collection
    {
        return $this->getActive($columns);
    }

    /**
     * Contar juegos por estado
     *
     * @return array Array con conteo por estado
     */
    public function getCountByStatus(): array
    {
        $counts = $this->model->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'ACTIVE' => $counts['ACTIVE'] ?? 0,
            'INACTIVE' => $counts['INACTIVE'] ?? 0,
            'MAINTENANCE' => $counts['MAINTENANCE'] ?? 0,
            'total' => array_sum($counts)
        ];
    }

    /**
     * Obtener juegos por múltiples nombres
     *
     * @param array $names Array de nombres
     * @param array $columns Columnas a seleccionar
     * @return Collection<Game>
     */
    public function findByMultipleNames(array $names, array $columns = ['*']): Collection
    {
        $names = array_map('strtoupper', $names);

        return $this->model->whereIn('name', $names)
            ->orderBy('name')
            ->get($columns);
    }

    /**
     * Obtener juegos activos con conteo de elementos relacionados
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Game>
     */
    public function getActiveWithCounts(array $columns = ['*']): Collection
    {
        return $this->model->where('status', 'ACTIVE')
            ->orderBy('name')
            ->get($columns);
    }

    /**
     * Cambiar estado de un juego
     *
     * @param string $gameId ID del juego
     * @param string $status Nuevo estado
     * @return bool
     */
    public function changeStatus(string $gameId, string $status): bool
    {
        return $this->model->where('id', $gameId)
            ->update(['status' => strtoupper($status)]) > 0;
    }

    /**
     * Verificar si un juego está activo
     *
     * @param string $gameId ID del juego
     * @return bool
     */
    public function isActive(string $gameId): bool
    {
        return $this->model->where('id', $gameId)
            ->where('status', 'ACTIVE')
            ->exists();
    }

    /**
     * Verificar si un juego está en mantenimiento
     *
     * @param string $gameId ID del juego
     * @return bool
     */
    public function isInMaintenance(string $gameId): bool
    {
        return $this->model->where('id', $gameId)
            ->where('status', 'MAINTENANCE')
            ->exists();
    }

    // =================================
    // Métodos legacy mantenidos temporalmente
    // =================================

    /**
     * Obtener juego por ID (método legacy)
     *
     * @param string $id ID del juego
     * @param array $fields Campos a seleccionar
     * @return Game|null
     */
    public function getById(string $id, array $fields = ['*']): ?Game
    {
        return $this->find($id, $fields);
    }
}