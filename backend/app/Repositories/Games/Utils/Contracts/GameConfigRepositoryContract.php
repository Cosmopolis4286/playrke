<?php

namespace App\Repositories\Utils\Contracts;

use App\Models\Shared\Utils\GameConfig;
use App\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de configuraciones de juego
 * Define métodos específicos para operaciones con GameConfig
 */
interface GameConfigRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Obtener configuración por juego y parámetro
     *
     * @param string $gameId ID del juego
     * @param string $param Nombre del parámetro
     * @param array $columns Columnas a seleccionar
     * @return GameConfig|null
     */
    public function getGameParam(string $gameId, string $param, array $columns = ['*']): ?GameConfig;

    /**
     * Obtener valor de un parámetro específico
     *
     * @param string $gameId ID del juego
     * @param string $param Nombre del parámetro
     * @param string $valueColumn Columna del valor a obtener
     * @return mixed
     */
    public function getGameParamValue(string $gameId, string $param, string $valueColumn = 'value'): mixed;

    /**
     * Obtener todas las configuraciones de un juego
     *
     * @param string $gameId ID del juego
     * @param array $columns Columnas a seleccionar
     * @return Collection<GameConfig>
     */
    public function getGameConfigs(string $gameId, array $columns = ['*']): Collection;

    /**
     * Obtener configuraciones activas de un juego
     *
     * @param string $gameId ID del juego
     * @param array $columns Columnas a seleccionar
     * @return Collection<GameConfig>
     */
    public function getActiveGameConfigs(string $gameId, array $columns = ['*']): Collection;

    /**
     * Obtener configuraciones inactivas de un juego
     *
     * @param string $gameId ID del juego
     * @param array $columns Columnas a seleccionar
     * @return Collection<GameConfig>
     */
    public function getInactiveGameConfigs(string $gameId, array $columns = ['*']): Collection;

    /**
     * Buscar configuraciones por parámetro
     *
     * @param string $param Nombre del parámetro
     * @param array $columns Columnas a seleccionar
     * @return Collection<GameConfig>
     */
    public function getByParam(string $param, array $columns = ['*']): Collection;

    /**
     * Buscar configuraciones por valor
     *
     * @param string $value Valor a buscar
     * @param array $columns Columnas a seleccionar
     * @return Collection<GameConfig>
     */
    public function getByValue(string $value, array $columns = ['*']): Collection;

    /**
     * Verificar si existe una configuración
     *
     * @param string $gameId ID del juego
     * @param string $param Nombre del parámetro
     * @return bool
     */
    public function existsGameParam(string $gameId, string $param): bool;

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
    public function setGameParam(string $gameId, string $param, string $value, ?string $description = null, bool $isActive = true): GameConfig;

    /**
     * Eliminar configuración específica
     *
     * @param string $gameId ID del juego
     * @param string $param Nombre del parámetro
     * @return bool
     */
    public function removeGameParam(string $gameId, string $param): bool;

    /**
     * Obtener configuraciones ordenadas por parámetro
     *
     * @param string $gameId ID del juego
     * @param array $columns Columnas a seleccionar
     * @return Collection<GameConfig>
     */
    public function getGameConfigsOrderedByParam(string $gameId, array $columns = ['*']): Collection;

    /**
     * Buscar configuraciones por término en descripción
     *
     * @param string $searchTerm Término de búsqueda
     * @param array $columns Columnas a seleccionar
     * @return Collection<GameConfig>
     */
    public function searchByDescription(string $searchTerm, array $columns = ['*']): Collection;

    /**
     * Activar/desactivar configuración
     *
     * @param string $gameId ID del juego
     * @param string $param Nombre del parámetro
     * @param bool $isActive Nuevo estado
     * @return bool
     */
    public function toggleGameParam(string $gameId, string $param, bool $isActive): bool;

    /**
     * Obtener configuraciones por múltiples juegos
     *
     * @param array $gameIds Array de IDs de juegos
     * @param array $columns Columnas a seleccionar
     * @return Collection<GameConfig>
     */
    public function getConfigsForMultipleGames(array $gameIds, array $columns = ['*']): Collection;
}