<?php

namespace App\Contracts;

use App\Models\Country;
use App\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de países
 * Define métodos específicos para operaciones con Country
 */
interface CountryRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Buscar país por código ISO de 2 letras
     *
     * @param string $isoCode2 Código ISO de 2 letras
     * @param array $columns Columnas a seleccionar
     * @return Country|null
     */
    public function findByIsoCode2(string $isoCode2, array $columns = ['*']): ?Country;

    /**
     * Buscar país por código ISO de 3 letras
     *
     * @param string $isoCode3 Código ISO de 3 letras
     * @param array $columns Columnas a seleccionar
     * @return Country|null
     */
    public function findByIsoCode3(string $isoCode3, array $columns = ['*']): ?Country;

    /**
     * Buscar país por nombre
     *
     * @param string $name Nombre del país
     * @param array $columns Columnas a seleccionar
     * @return Country|null
     */
    public function findByName(string $name, array $columns = ['*']): ?Country;

    /**
     * Obtener países activos
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Country>
     */
    public function getActive(array $columns = ['*']): Collection;

    /**
     * Obtener países inactivos
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Country>
     */
    public function getInactive(array $columns = ['*']): Collection;

    /**
     * Obtener países bloqueados
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Country>
     */
    public function getBlocked(array $columns = ['*']): Collection;

    /**
     * Buscar países por nombre parcial
     *
     * @param string $searchTerm Término de búsqueda
     * @param array $columns Columnas a seleccionar
     * @return Collection<Country>
     */
    public function searchByName(string $searchTerm, array $columns = ['*']): Collection;

    /**
     * Verificar si existe un país por código ISO
     *
     * @param string $isoCode Código ISO (2 o 3 letras)
     * @return bool
     */
    public function existsByIsoCode(string $isoCode): bool;

    /**
     * Obtener países ordenados por nombre
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Country>
     */
    public function getAllOrderedByName(array $columns = ['*']): Collection;

    /**
     * Obtener países con vendedores
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Country>
     */
    public function getWithSellers(array $columns = ['*']): Collection;

    /**
     * Obtener países con jugadores externos
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Country>
     */
    public function getWithExternalPlayers(array $columns = ['*']): Collection;
}
