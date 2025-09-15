<?php

namespace App\Repositories\Commands\Contracts;

use App\Models\Commands\CommandLog;
use App\Repositories\Contracts\BaseRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de logs de comandos
 * Define métodos específicos para operaciones con CommandLog
 */
interface CommandLogRepositoryContract extends BaseRepositoryContract
{
    /**
     * Obtener logs por comando específico
     *
     * @param string $command Nombre del comando
     * @param array $columns Columnas a seleccionar
     * @return Collection<CommandLog>
     */
    public function getByCommand(string $command, array $columns = ['*']): Collection;

    /**
     * Obtener logs por estado
     *
     * @param string $status Estado del comando (success/error)
     * @param array $columns Columnas a seleccionar
     * @return Collection<CommandLog>
     */
    public function getByStatus(string $status, array $columns = ['*']): Collection;

    /**
     * Obtener logs de errores
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<CommandLog>
     */
    public function getErrors(array $columns = ['*']): Collection;

    /**
     * Obtener logs exitosos
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<CommandLog>
     */
    public function getSuccessful(array $columns = ['*']): Collection;

    /**
     * Obtener logs recientes (últimos N registros)
     *
     * @param int $limit Cantidad de registros
     * @param array $columns Columnas a seleccionar
     * @return Collection<CommandLog>
     */
    public function getRecent(int $limit = 50, array $columns = ['*']): Collection;

    /**
     * Obtener logs por rango de fechas
     *
     * @param string $startDate Fecha de inicio
     * @param string $endDate Fecha de fin
     * @param array $columns Columnas a seleccionar
     * @return Collection<CommandLog>
     */
    public function getByDateRange(string $startDate, string $endDate, array $columns = ['*']): Collection;

    /**
     * Contar comandos por estado
     *
     * @return array Array con conteo por estado
     */
    public function getCountByStatus(): array;

    /**
     * Limpiar logs antiguos
     *
     * @param int $days Días de antigüedad
     * @return int Número de registros eliminados
     */
    public function cleanOldLogs(int $days = 30): int;
}