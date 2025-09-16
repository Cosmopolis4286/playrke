<?php

namespace App\Repositories\Countries;

use App\Models\Countries\Country;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Countries\Contracts\CountryRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositorio para operaciones con países
 * Maneja todas las consultas complejas relacionadas con Country
 */
class CountryRepository extends BaseRepository implements CountryRepositoryContract
{
    public function __construct(Country $model)
    {
        parent::__construct($model);
    }

    /**
     * Buscar país por código ISO de 2 letras
     *
     * @param string $isoCode2 Código ISO de 2 letras
     * @param array $columns Columnas a seleccionar
     * @return Country|null
     */
    public function findByIsoCode2(string $isoCode2, array $columns = ['*']): ?Country
    {
        return $this->model->where('iso_code_2', strtoupper($isoCode2))->first($columns);
    }

    /**
     * Buscar país por código ISO de 3 letras
     *
     * @param string $isoCode3 Código ISO de 3 letras
     * @param array $columns Columnas a seleccionar
     * @return Country|null
     */
    public function findByIsoCode3(string $isoCode3, array $columns = ['*']): ?Country
    {
        return $this->model->where('iso_code_3', strtoupper($isoCode3))->first($columns);
    }

    /**
     * Buscar país por nombre
     *
     * @param string $name Nombre del país
     * @param array $columns Columnas a seleccionar
     * @return Country|null
     */
    public function findByName(string $name, array $columns = ['*']): ?Country
    {
        return $this->model->where('name', $name)->first($columns);
    }

    /**
     * Obtener países por estado
     *
     * @param string $status Estado del país (ACTIVE/INACTIVE/BLOCKED)
     * @param array $columns Columnas a seleccionar
     * @return Collection<Country>
     */
    public function getByStatus(string $status, array $columns = ['*']): Collection
    {
        return $this->model->where('status', strtoupper($status))
            ->orderBy('name')
            ->get($columns);
    }

    /**
     * Obtener países activos
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Country>
     */
    public function getActive(array $columns = ['*']): Collection
    {
        return $this->getByStatus('ACTIVE', $columns);
    }

    /**
     * Obtener países inactivos
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Country>
     */
    public function getInactive(array $columns = ['*']): Collection
    {
        return $this->getByStatus('INACTIVE', $columns);
    }

    /**
     * Obtener países bloqueados
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Country>
     */
    public function getBlocked(array $columns = ['*']): Collection
    {
        return $this->getByStatus('BLOCKED', $columns);
    }

    /**
     * Buscar países por nombre parcial
     *
     * @param string $searchTerm Término de búsqueda
     * @param array $columns Columnas a seleccionar
     * @return Collection<Country>
     */
    public function searchByName(string $searchTerm, array $columns = ['*']): Collection
    {
        return $this->model->where('name', 'like', "%{$searchTerm}%")
            ->orderBy('name')
            ->get($columns);
    }

    /**
     * Verificar si existe un país por código ISO
     *
     * @param string $isoCode Código ISO (2 o 3 letras)
     * @return bool
     */
    public function existsByIsoCode(string $isoCode): bool
    {
        $isoCode = strtoupper($isoCode);

        return $this->model->where('iso_code_2', $isoCode)
            ->orWhere('iso_code_3', $isoCode)
            ->exists();
    }

    /**
     * Obtener países ordenados por nombre
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Country>
     */
    public function getAllOrderedByName(array $columns = ['*']): Collection
    {
        return $this->model->orderBy('name')->get($columns);
    }

    /**
     * Obtener países con vendedores
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Country>
     */
    public function getWithSellers(array $columns = ['*']): Collection
    {
        return $this->model->whereHas('sellers')
            ->with('sellers')
            ->orderBy('name')
            ->get($columns);
    }

    /**
     * Obtener países con jugadores externos
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Country>
     */
    public function getWithExternalPlayers(array $columns = ['*']): Collection
    {
        return $this->model->whereHas('externalPlayers')
            ->with('externalPlayers')
            ->orderBy('name')
            ->get($columns);
    }

    /**
     * Obtener países con conteo de vendedores
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Country>
     */
    public function getWithSellersCount(array $columns = ['*']): Collection
    {
        return $this->model->withCount('sellers')
            ->orderBy('name')
            ->get($columns);
    }

    /**
     * Obtener países con conteo de jugadores externos
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Country>
     */
    public function getWithExternalPlayersCount(array $columns = ['*']): Collection
    {
        return $this->model->withCount('externalPlayers')
            ->orderBy('name')
            ->get($columns);
    }

    /**
     * Obtener países por múltiples códigos ISO
     *
     * @param array $isoCodes Array de códigos ISO
     * @param array $columns Columnas a seleccionar
     * @return Collection<Country>
     */
    public function findByMultipleIsoCodes(array $isoCodes, array $columns = ['*']): Collection
    {
        $isoCodes = array_map('strtoupper', $isoCodes);

        return $this->model->where(function ($query) use ($isoCodes) {
            $query->whereIn('iso_code_2', $isoCodes)
                  ->orWhereIn('iso_code_3', $isoCodes);
        })->orderBy('name')->get($columns);
    }

    // =================================
    // Métodos legacy mantenidos temporalmente
    // =================================

    /**
     * Obtener país por código ISO (método legacy)
     *
     * @param string $isoCode Código ISO
     * @param int $length Longitud del código (2 o 3)
     * @return Country|null
     */
    public function getCountryByIsoCode(string $isoCode, int $length = 2): ?Country
    {
        return $length === 2
            ? $this->findByIsoCode2($isoCode)
            : $this->findByIsoCode3($isoCode);
    }

    /**
     * Obtener países activos (método legacy)
     *
     * @param array $fields Campos a seleccionar
     * @return Collection<Country>
     */
    public function getAllAvailableCountries(array $fields = ['*']): Collection
    {
        return $this->getActive($fields);
    }

    /**
     * Obtener países por IDs (método legacy)
     *
     * @param array $ids Array de IDs
     * @param array $fields Campos a seleccionar
     * @return Collection<Country>
     */
    public function getCountriesById(array $ids, array $fields = ['*']): Collection
    {
        return $this->model->whereIn('id', $ids)->get($fields);
    }

    /**
     * Obtener países activos por IDs (método legacy)
     *
     * @param array $ids Array de IDs
     * @param array $fields Campos a seleccionar
     * @return Collection<Country>
     */
    public function getCountries(array $ids, array $fields = ['*']): Collection
    {
        return $this->model->where('status', 'ACTIVE')
            ->whereIn('id', $ids)
            ->get($fields);
    }
}
