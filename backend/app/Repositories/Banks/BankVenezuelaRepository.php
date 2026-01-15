<?php

namespace App\Repositories\Banks;

use App\Models\Banks\BankVenezuela;
use App\Repositories\BaseRepository;
use App\Repositories\Banks\Contracts\BankVenezuelaRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositorio para operaciones con bancos de Venezuela
 * Maneja todas las consultas complejas relacionadas con BankVenezuela
 */
class BankVenezuelaRepository extends BaseRepository implements BankVenezuelaRepositoryContract
{
    public function __construct(BankVenezuela $model)
    {
        parent::__construct($model);
    }

    /**
     * Buscar banco por código
     *
     * @param string $code Código del banco
     * @param array $columns Columnas a seleccionar
     * @return BankVenezuela|null
     */
    public function findByCode(string $code, array $columns = ['*']): ?BankVenezuela
    {
        return $this->model->where('code', $code)->first($columns);
    }

    /**
     * Obtener valor específico de un banco por código
     *
     * @param string $code Código del banco
     * @param string $column Columna a obtener
     * @return mixed
     */
    public function getValueByCode(string $code, string $column): mixed
    {
        return $this->model->where('code', $code)->value($column);
    }

    /**
     * Verificar si existe un banco por código
     *
     * @param string $code Código del banco
     * @return bool
     */
    public function existsByCode(string $code): bool
    {
        return $this->model->where('code', $code)->exists();
    }

    /**
     * Buscar bancos por nombre exacto
     *
     * @param string $name Nombre del banco
     * @param array $columns Columnas a seleccionar
     * @return Collection<BankVenezuela>
     */
    public function findByName(string $name, array $columns = ['*']): Collection
    {
        return $this->model->where('name', $name)->get($columns);
    }

    /**
     * Buscar bancos que contengan el nombre especificado
     *
     * @param string $searchTerm Término de búsqueda
     * @param array $columns Columnas a seleccionar
     * @return Collection<BankVenezuela>
     */
    public function searchByName(string $searchTerm, array $columns = ['*']): Collection
    {
        return $this->model->where('name', 'like', "%{$searchTerm}%")
            ->orderBy('name')
            ->get($columns);
    }

    /**
     * Obtener bancos que tienen pagos móviles asociados
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<BankVenezuela>
     */
    public function getBanksWithPagoMovil(array $columns = ['*']): Collection
    {
        return $this->model->whereHas('pagoMoviles')
            ->with('pagoMoviles')
            ->orderBy('name')
            ->get($columns);
    }

    /**
     * Obtener todos los bancos ordenados por nombre
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<BankVenezuela>
     */
    public function getAllOrderedByName(array $columns = ['*']): Collection
    {
        return $this->model->orderBy('name')->get($columns);
    }

    /**
     * Buscar por múltiples códigos
     *
     * @param array $codes Array de códigos de banco
     * @param array $columns Columnas a seleccionar
     * @return Collection<BankVenezuela>
     */
    public function findByMultipleCodes(array $codes, array $columns = ['*']): Collection
    {
        return $this->model->whereIn('code', $codes)->get($columns);
    }

    /**
     * Contar pagos móviles por banco
     *
     * @param string $bankId ID del banco
     * @return int
     */
    public function countPagoMovilesByBank(string $bankId): int
    {
        return $this->model->where('id', $bankId)
            ->withCount('pagoMoviles')
            ->first()
            ->pago_moviles_count ?? 0;
    }
}