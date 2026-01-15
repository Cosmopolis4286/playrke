<?php

namespace App\Contracts\Banks;

use App\Models\Banks\BankVenezuela;
use App\Contracts\BaseRepositoryInterface;

/**
 * Contrato para el repositorio de bancos de Venezuela
 * Define métodos específicos para operaciones con BankVenezuela
 */
interface BankVenezuelaRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Buscar banco por código
     *
     * @param string $code Código del banco
     * @param array $columns Columnas a seleccionar
     * @return BankVenezuela|null
     */
    public function findByCode(string $code, array $columns = ['*']): ?BankVenezuela;

    /**
     * Obtener valor específico de un banco por código
     *
     * @param string $code Código del banco
     * @param string $column Columna a obtener
     * @return mixed
     */
    public function getValueByCode(string $code, string $column): mixed;

    /**
     * Verificar si existe un banco por código
     *
     * @param string $code Código del banco
     * @return bool
     */
    public function existsByCode(string $code): bool;

    /**
     * Buscar bancos por nombre
     *
     * @param string $name Nombre del banco
     * @param array $columns Columnas a seleccionar
     * @return \Illuminate\Database\Eloquent\Collection<BankVenezuela>
     */
    public function findByName(string $name, array $columns = ['*']): \Illuminate\Database\Eloquent\Collection;

    /**
     * Buscar bancos que contengan el nombre especificado
     *
     * @param string $searchTerm Término de búsqueda
     * @param array $columns Columnas a seleccionar
     * @return \Illuminate\Database\Eloquent\Collection<BankVenezuela>
     */
    public function searchByName(string $searchTerm, array $columns = ['*']): \Illuminate\Database\Eloquent\Collection;

    /**
     * Obtener bancos que tienen pagos móviles asociados
     *
     * @param array $columns Columnas a seleccionar
     * @return \Illuminate\Database\Eloquent\Collection<BankVenezuela>
     */
    public function getBanksWithPagoMovil(array $columns = ['*']): \Illuminate\Database\Eloquent\Collection;
}