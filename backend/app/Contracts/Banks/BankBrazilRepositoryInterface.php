<?php

namespace App\Contracts\Banks;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Banks\BankBrazil;

/**
 * Contrato para el repositorio de bancos de Brasil
 * Define métodos específicos para operaciones con BankBrazil
 */
interface BankBrazilRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Verificar si existe un banco por ISPB
     *
     * @param string $ispb Código ISPB
     */
    public function existsByISPB(string $ispb) : bool;

    /**
     * Buscar banco por código ISPB
     *
     * @param string $ispb    Código ISPB del banco
     * @param array  $columns Columnas a seleccionar
     */
    public function findByISPB(string $ispb, array $columns = ['*']) : ?BankBrazil;

    /**
     * Buscar bancos por nombre
     *
     * @param string $name    Nombre del banco
     * @param array  $columns Columnas a seleccionar
     *
     * @return \Illuminate\Database\Eloquent\Collection<BankBrazil>
     */
    public function findByName(string $name, array $columns = ['*']) : \Illuminate\Database\Eloquent\Collection;

    /**
     * Obtiene o crea un banco por ISPB.
     * Usado al sincronizar con la BrasilAPI.
     *
     * @param string $ispb Código ISPB del banco
     * @param array  $data Datos a insertar si no existe (name, full_name, ispb)
     */
    public function firstOrCreateByISPB(string $ispb, array $data) : BankBrazil;

    /**
     * Obtener todos los bancos ordenados por nombre.
     *
     * @param array $columns Columnas a seleccionar
     *
     * @return \Illuminate\Database\Eloquent\Collection<BankBrazil>
     */
    public function getAllOrderedByName(array $columns = ['*']) : \Illuminate\Database\Eloquent\Collection;

    /**
     * Obtener valor específico de un banco por ISPB
     *
     * @param string $ispb   Código ISPB
     * @param string $column Columna a obtener
     */
    public function getValueByISPB(string $ispb, string $column) : mixed;

    /**
     * Verifica si la tabla de bancos de Brasil está vacía.
     */
    public function isEmpty() : bool;

    /**
     * Buscar bancos que contengan el nombre especificado
     *
     * @param string $searchTerm Término de búsqueda
     * @param array  $columns    Columnas a seleccionar
     *
     * @return \Illuminate\Database\Eloquent\Collection<BankBrazil>
     */
    public function searchByName(string $searchTerm, array $columns = ['*']) : \Illuminate\Database\Eloquent\Collection;
}
