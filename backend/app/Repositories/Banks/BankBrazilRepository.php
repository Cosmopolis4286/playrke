<?php

namespace App\Repositories\Banks;

use App\Models\Banks\BankBrazil;
use App\Repositories\BaseRepository;
use App\Repositories\Banks\Contracts\BankBrazilRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositorio para operaciones con bancos de Brasil
 *
 * Maneja todas las consultas complejas relacionadas con BankBrazil
 */
class BankBrazilRepository extends BaseRepository implements BankBrazilRepositoryContract
{
    /**
     * Constructor
     *
     * @param BankBrazil $model
     */
    public function __construct(BankBrazil $model)
    {
        parent::__construct($model);
    }

    /**
     * Buscar banco por código ISPB
     *
     * @param string $ispb Código ISPB del banco
     * @param array $columns Columnas a seleccionar
     * @return BankBrazil|null
     */
    public function findByISPB(string $ispb, array $columns = ['*']): ?BankBrazil
    {
        return $this->model->where('ispb', $ispb)->first($columns);
    }

    /**
     * Obtener valor específico de un banco por ISPB
     *
     * @param string $ispb Código ISPB
     * @param string $column Columna a obtener
     * @return mixed
     */
    public function getValueByISPB(string $ispb, string $column): mixed
    {
        return $this->model->where('ispb', $ispb)->value($column);
    }

    /**
     * Verificar si existe un banco por ISPB
     *
     * @param string $ispb Código ISPB
     * @return bool
     */
    public function existsByISPB(string $ispb): bool
    {
        return $this->model->where('ispb', $ispb)->exists();
    }

    /**
     * Buscar bancos por nombre exacto
     *
     * @param string $name Nombre del banco
     * @param array $columns Columnas a seleccionar
     * @return Collection<BankBrazil>
     */
    public function findByName(string $name, array $columns = ['*']): Collection
    {
        return $this->model->where('name', $name)
            ->orWhere('full_name', $name)
            ->get($columns);
    }

    /**
     * Buscar bancos que contengan el nombre especificado
     *
     * @param string $searchTerm Término de búsqueda
     * @param array $columns Columnas a seleccionar
     * @return Collection<BankBrazil>
     */
    public function searchByName(string $searchTerm, array $columns = ['*']): Collection
    {
        return $this->model->where('name', 'like', "%{$searchTerm}%")
            ->orWhere('full_name', 'like', "%{$searchTerm}%")
            ->orderBy('name')
            ->get($columns);
    }

    /**
     * Obtener todos los bancos ordenados por nombre
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<BankBrazil>
     */
    public function getAllOrderedByName(array $columns = ['*']): Collection
    {
        return $this->model->orderBy('name')->get($columns);
    }

    /**
     * Buscar por múltiples ISPBs
     *
     * @param array $ispbs Array de códigos ISPB
     * @param array $columns Columnas a seleccionar
     * @return Collection<BankBrazil>
     */
    public function findByMultipleISPBs(array $ispbs, array $columns = ['*']): Collection
    {
        return $this->model->whereIn('ispb', $ispbs)->get($columns);
    }
}
