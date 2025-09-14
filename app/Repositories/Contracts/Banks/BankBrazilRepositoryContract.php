<?php

namespace App\Repositories\Contracts\Banks;

use App\Models\Banks\BankBrazil;
use App\Repositories\Contracts\BaseRepositoryContract;

/**
 * Contrato para el repositorio de bancos de Brasil
 * Define métodos específicos para operaciones con BankBrazil
 */
interface BankBrazilRepositoryContract extends BaseRepositoryContract
{
    /**
     * Buscar banco por código ISPB
     *
     * @param string $ispb Código ISPB del banco
     * @param array $columns Columnas a seleccionar
     * @return BankBrazil|null
     */
    public function findByISPB(string $ispb, array $columns = ['*']): ?BankBrazil;

    /**
     * Obtener valor específico de un banco por ISPB
     *
     * @param string $ispb Código ISPB
     * @param string $column Columna a obtener
     * @return mixed
     */
    public function getValueByISPB(string $ispb, string $column): mixed;

    /**
     * Verificar si existe un banco por ISPB
     *
     * @param string $ispb Código ISPB
     * @return bool
     */
    public function existsByISPB(string $ispb): bool;

    /**
     * Buscar bancos por nombre
     *
     * @param string $name Nombre del banco
     * @param array $columns Columnas a seleccionar
     * @return \Illuminate\Database\Eloquent\Collection<BankBrazil>
     */
    public function findByName(string $name, array $columns = ['*']): \Illuminate\Database\Eloquent\Collection;

    /**
     * Buscar bancos que contengan el nombre especificado
     *
     * @param string $searchTerm Término de búsqueda
     * @param array $columns Columnas a seleccionar
     * @return \Illuminate\Database\Eloquent\Collection<BankBrazil>
     */
    public function searchByName(string $searchTerm, array $columns = ['*']): \Illuminate\Database\Eloquent\Collection;
}