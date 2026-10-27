<?php

namespace App\Repositories\Banks;

use App\Contracts\Banks\BankBrazilRepositoryInterface;
use App\Models\Banks\BankBrazil;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositorio para operaciones con bancos de Brasil
 *
 * Maneja todas las consultas complejas relacionadas con BankBrazil
 */
class BankBrazilRepository extends BaseRepository implements BankBrazilRepositoryInterface
{
    /**
     * Constructor
     */
    public function __construct(BankBrazil $model)
    {
        $this->model = $model;
    }

    /**
     * Verificar si existe un banco por ISPB
     *
     * @param string $ispb Código ISPB
     */
    public function existsByISPB(string $ispb) : bool
    {
        return $this->model->where('ispb', $ispb)->exists();
    }

    /**
     * Buscar banco por código ISPB
     *
     * @param string $ispb    Código ISPB del banco
     * @param array  $columns Columnas a seleccionar
     */
    public function findByISPB(string $ispb, array $columns = ['*']) : ?BankBrazil
    {
        return $this->model->where('ispb', $ispb)->first($columns);
    }

    /**
     * Buscar por múltiples ISPBs
     *
     * @param array $ispbs   Array de códigos ISPB
     * @param array $columns Columnas a seleccionar
     *
     * @return Collection<BankBrazil>
     */
    public function findByMultipleISPBs(array $ispbs, array $columns = ['*']) : Collection
    {
        return $this->model->whereIn('ispb', $ispbs)->get($columns);
    }

    /**
     * Buscar bancos por nombre exacto
     *
     * @param string $name    Nombre del banco
     * @param array  $columns Columnas a seleccionar
     *
     * @return Collection<BankBrazil>
     */
    public function findByName(string $name, array $columns = ['*']) : Collection
    {
        return $this->model->where('name', $name)
            ->orWhere('full_name', $name)
            ->get($columns);
    }

    /**
     * Obtiene o crea un banco por ISPB.
     * Si ya existe (por ISPB) lo retorna; si no, lo crea con los datos provistos.
     *
     * @param string $ispb Código ISPB del banco
     * @param array  $data Datos a insertar si no existe (name, full_name, ispb)
     */
    public function firstOrCreateByISPB(string $ispb, array $data) : BankBrazil
    {
        return $this->model->firstOrCreate(['ispb' => $ispb], $data);
    }

    /**
     * Obtener todos los bancos ordenados por nombre
     *
     * @param array $columns Columnas a seleccionar
     *
     * @return Collection<BankBrazil>
     */
    public function getAllOrderedByName(array $columns = ['*']) : Collection
    {
        return $this->model->orderBy('name')->get($columns);
    }

    /**
     * Obtener valor específico de un banco por ISPB
     *
     * @param string $ispb   Código ISPB
     * @param string $column Columna a obtener
     */
    public function getValueByISPB(string $ispb, string $column) : mixed
    {
        return $this->model->where('ispb', $ispb)->value($column);
    }

    /**
     * Verifica si la tabla de bancos de Brasil está vacía.
     */
    public function isEmpty() : bool
    {
        return ! $this->model->exists();
    }

    /**
     * Buscar bancos que contengan el nombre especificado
     *
     * @param string $searchTerm Término de búsqueda
     * @param array  $columns    Columnas a seleccionar
     *
     * @return Collection<BankBrazil>
     */
    public function searchByName(string $searchTerm, array $columns = ['*']) : Collection
    {
        return $this->model->where('name', 'like', "%{$searchTerm}%")
            ->orWhere('full_name', 'like', "%{$searchTerm}%")
            ->orderBy('name')
            ->get($columns);
    }
}
