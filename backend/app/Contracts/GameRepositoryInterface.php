<?php

namespace App\Contracts;

use App\Models\Game;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de juegos
 * Define métodos específicos para operaciones con Game
 */
interface GameRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Verificar si existe un juego por nombre
     *
     * @param string $name Nombre del juego
     */
    public function existsByName(string $name) : bool;

    /**
     * Obtener juegos por múltiples nombres
     *
     * @param array $names   Array de nombres
     * @param array $columns Columnas a seleccionar
     *
     * @return Collection<Game>
     */
    public function findByMultipleNames(array $names, array $columns = ['*']) : Collection;

    /**
     * Buscar juego por nombre
     *
     * @param string $name    Nombre del juego
     * @param array  $columns Columnas a seleccionar
     */
    public function findByName(string $name, array $columns = ['*']) : ?Game;

    /**
     * Obtener juegos activos
     *
     * @param array $columns Columnas a seleccionar
     *
     * @return Collection<Game>
     */
    public function getActive(array $columns = ['*']) : Collection;

    /**
     * Obtener juegos ordenados por nombre
     *
     * @param array $columns Columnas a seleccionar
     *
     * @return Collection<Game>
     */
    public function getAllOrderedByName(array $columns = ['*']) : Collection;

    /**
     * Obtener juegos disponibles (método legacy)
     *
     * @param array $columns Columnas a seleccionar
     *
     * @return Collection<Game>
     */
    public function getAvailableGames(array $columns = ['*']) : Collection;

    /**
     * Contar juegos por estado
     *
     * @return array Array con conteo por estado
     */
    public function getCountByStatus() : array;

    /**
     * Obtener juegos inactivos
     *
     * @param array $columns Columnas a seleccionar
     *
     * @return Collection<Game>
     */
    public function getInactive(array $columns = ['*']) : Collection;

    /**
     * Obtener juegos en mantenimiento
     *
     * @param array $columns Columnas a seleccionar
     *
     * @return Collection<Game>
     */
    public function getInMaintenance(array $columns = ['*']) : Collection;

    /**
     * Obtener valor específico de un juego por nombre
     *
     * @param string $name   Nombre del juego
     * @param string $column Columna a obtener
     */
    public function getValueByName(string $name, string $column) : mixed;

    /**
     * Buscar juegos por nombre parcial
     *
     * @param string $searchTerm Término de búsqueda
     * @param array  $columns    Columnas a seleccionar
     *
     * @return Collection<Game>
     */
    public function searchByName(string $searchTerm, array $columns = ['*']) : Collection;
}
