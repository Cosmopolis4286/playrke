<?php

namespace App\Contracts\Banks;

use App\Models\Banks\BankBrazil;
use App\Contracts\BaseRepositoryInterface;

/**
 * Contrato para el repositorio de bancos de Brasil
 * Define métodos específicos para operaciones con BankBrazil
 */
interface BankBrazilRepositoryInterface extends BaseRepositoryInterface
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

    /**
     * Obtiene o crea un banco por ISPB.
     * Usado al sincronizar con la BrasilAPI.
     *
     * @param string $ispb   Código ISPB del banco
     * @param array  $data   Datos a insertar si no existe (name, full_name, ispb)
     * @return BankBrazil
     */
    public function firstOrCreateByISPB(string $ispb, array $data): BankBrazil;
}