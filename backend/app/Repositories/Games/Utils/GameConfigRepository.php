<?php

namespace App\Repositories\Games\Utils;

use App\Models\Shared\Utils\GameConfig;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Games\Utils\Contracts\GameConfigRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositorio para operaciones con configuraciones de juego
 * Maneja todas las consultas complejas relacionadas con GameConfig
 */
class GameConfigRepository extends BaseRepository implements GameConfigRepositoryContract
{
    public function __construct(GameConfig $model)
    {
        parent::__construct($model);
    }

    /**
     * Obtener configuración por juego y parámetro
     *
     * @param string $gameId ID del juego
     * @param string $param Nombre del parámetro
     * @param array $columns Columnas a seleccionar
     * @return GameConfig|null
     */
    public function getGameParam(string $gameId, string $param, array $columns = ['*']): ?GameConfig
    {
        return $this->model->where('game_id', $gameId)
            ->where('param', $param)
            ->first($columns);
    }

    /**
     * Obtener valor de un parámetro específico
     *
     * @param string $gameId ID del juego
     * @param string $param Nombre del parámetro
     * @param string $valueColumn Columna del valor a obtener
     * @return mixed
     */
    public function getGameParamValue(string $gameId, string $param, string $valueColumn = 'value'): mixed
    {
        return $this->model->where('game_id', $gameId)
            ->where('param', $param)
            ->value($valueColumn);
    }

    /**
     * Obtener todas las configuraciones de un juego
     *
     * @param string $gameId ID del juego
     * @param array $columns Columnas a seleccionar
     * @return Collection<GameConfig>
     */
    public function getGameConfigs(string $gameId, array $columns = ['*']): Collection
    {
        return $this->model->where('game_id', $gameId)
            ->orderBy('param')
            ->get($columns);
    }

    /**
     * Obtener configuraciones activas de un juego
     *
     * @param string $gameId ID del juego
     * @param array $columns Columnas a seleccionar
     * @return Collection<GameConfig>
     */
    public function getActiveGameConfigs(string $gameId, array $columns = ['*']): Collection
    {
        return $this->model->where('game_id', $gameId)
            ->where('status', true)
            ->orderBy('param')
            ->get($columns);
    }

    /**
     * Obtener configuraciones inactivas de un juego
     *
     * @param string $gameId ID del juego
     * @param array $columns Columnas a seleccionar
     * @return Collection<GameConfig>
     */
    public function getInactiveGameConfigs(string $gameId, array $columns = ['*']): Collection
    {
        return $this->model->where('game_id', $gameId)
            ->where('status', false)
            ->orderBy('param')
            ->get($columns);
    }

    /**
     * Buscar configuraciones por parámetro
     *
     * @param string $param Nombre del parámetro
     * @param array $columns Columnas a seleccionar
     * @return Collection<GameConfig>
     */
    public function getByParam(string $param, array $columns = ['*']): Collection
    {
        return $this->model->where('param', $param)
            ->with('game')
            ->orderBy('game_id')
            ->get($columns);
    }

    /**
     * Buscar configuraciones por valor
     *
     * @param string $value Valor a buscar
     * @param array $columns Columnas a seleccionar
     * @return Collection<GameConfig>
     */
    public function getByValue(string $value, array $columns = ['*']): Collection
    {
        return $this->model->where('value', $value)
            ->with('game')
            ->orderBy('game_id')
            ->get($columns);
    }

    /**
     * Verificar si existe una configuración
     *
     * @param string $gameId ID del juego
     * @param string $param Nombre del parámetro
     * @return bool
     */
    public function existsGameParam(string $gameId, string $param): bool
    {
        return $this->model->where('game_id', $gameId)
            ->where('param', $param)
            ->exists();
    }

    /**
     * Crear o actualizar configuración
     *
     * @param string $gameId ID del juego
     * @param string $param Nombre del parámetro
     * @param string $value Valor del parámetro
     * @param string|null $description Descripción opcional
     * @param bool $isActive Estado activo/inactivo
     * @return GameConfig
     */
    public function setGameParam(string $gameId, string $param, string $value, ?string $description = null, bool $isActive = true): GameConfig
    {
        return $this->model->updateOrCreate(
            [
                'game_id' => $gameId,
                'param' => $param,
            ],
            [
                'value' => $value,
                'description' => $description,
                'status' => $isActive,
            ]
        );
    }

    /**
     * Eliminar configuración específica
     *
     * @param string $gameId ID del juego
     * @param string $param Nombre del parámetro
     * @return bool
     */
    public function removeGameParam(string $gameId, string $param): bool
    {
        return $this->model->where('game_id', $gameId)
            ->where('param', $param)
            ->delete() > 0;
    }

    /**
     * Obtener configuraciones ordenadas por parámetro
     *
     * @param string $gameId ID del juego
     * @param array $columns Columnas a seleccionar
     * @return Collection<GameConfig>
     */
    public function getGameConfigsOrderedByParam(string $gameId, array $columns = ['*']): Collection
    {
        return $this->model->where('game_id', $gameId)
            ->orderBy('param')
            ->get($columns);
    }

    /**
     * Buscar configuraciones por término en descripción
     *
     * @param string $searchTerm Término de búsqueda
     * @param array $columns Columnas a seleccionar
     * @return Collection<GameConfig>
     */
    public function searchByDescription(string $searchTerm, array $columns = ['*']): Collection
    {
        return $this->model->where('description', 'like', "%{$searchTerm}%")
            ->with('game')
            ->orderBy('game_id')
            ->orderBy('param')
            ->get($columns);
    }

    /**
     * Activar/desactivar configuración
     *
     * @param string $gameId ID del juego
     * @param string $param Nombre del parámetro
     * @param bool $isActive Nuevo estado
     * @return bool
     */
    public function toggleGameParam(string $gameId, string $param, bool $isActive): bool
    {
        return $this->model->where('game_id', $gameId)
            ->where('param', $param)
            ->update(['status' => $isActive]) > 0;
    }

    /**
     * Obtener configuraciones por múltiples juegos
     *
     * @param array $gameIds Array de IDs de juegos
     * @param array $columns Columnas a seleccionar
     * @return Collection<GameConfig>
     */
    public function getConfigsForMultipleGames(array $gameIds, array $columns = ['*']): Collection
    {
        return $this->model->whereIn('game_id', $gameIds)
            ->with('game')
            ->orderBy('game_id')
            ->orderBy('param')
            ->get($columns);
    }

    /**
     * Obtener configuraciones con game eager loaded
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<GameConfig>
     */
    public function getAllWithGame(array $columns = ['*']): Collection
    {
        return $this->model->with('game')
            ->orderBy('game_id')
            ->orderBy('param')
            ->get($columns);
    }

    /**
     * Clonar configuraciones de un juego a otro
     *
     * @param string $sourceGameId ID del juego fuente
     * @param string $targetGameId ID del juego destino
     * @param bool $overwrite Si debe sobrescribir configuraciones existentes
     * @return int Número de configuraciones clonadas
     */
    public function cloneGameConfigs(string $sourceGameId, string $targetGameId, bool $overwrite = false): int
    {
        $sourceConfigs = $this->getGameConfigs($sourceGameId);
        $cloned = 0;

        foreach ($sourceConfigs as $config) {
            if (!$overwrite && $this->existsGameParam($targetGameId, $config->param)) {
                continue;
            }

            $this->setGameParam(
                $targetGameId,
                $config->param,
                $config->value,
                $config->description,
                $config->status
            );

            $cloned++;
        }

        return $cloned;
    }

    /**
     * Obtener estadísticas de configuraciones por juego
     *
     * @param string $gameId ID del juego
     * @return array
     */
    public function getGameConfigStats(string $gameId): array
    {
        $total = $this->model->where('game_id', $gameId)->count();
        $active = $this->model->where('game_id', $gameId)->where('status', true)->count();
        $inactive = $total - $active;

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'percentage_active' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Buscar configuraciones por parámetro parcial
     *
     * @param string $paramSearch Término de búsqueda del parámetro
     * @param array $columns Columnas a seleccionar
     * @return Collection<GameConfig>
     */
    public function searchByParam(string $paramSearch, array $columns = ['*']): Collection
    {
        return $this->model->where('param', 'like', "%{$paramSearch}%")
            ->with('game')
            ->orderBy('game_id')
            ->orderBy('param')
            ->get($columns);
    }
}
