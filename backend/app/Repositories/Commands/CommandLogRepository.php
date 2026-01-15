<?php

namespace App\Repositories\Commands;

use App\Models\Commands\CommandLog;
use App\Repositories\BaseRepository;
use App\Repositories\Commands\Contracts\CommandLogRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

/**
 * Repositorio para operaciones con logs de comandos
 * Maneja todas las consultas complejas relacionadas con CommandLog
 */
class CommandLogRepository extends BaseRepository implements CommandLogRepositoryContract
{
    public function __construct(CommandLog $model)
    {
        parent::__construct($model);
    }

    /**
     * Obtener logs por comando específico
     *
     * @param string $command Nombre del comando
     * @param array $columns Columnas a seleccionar
     * @return Collection<CommandLog>
     */
    public function getByCommand(string $command, array $columns = ['*']): Collection
    {
        return $this->model->where('command', $command)
            ->orderBy('executed_at', 'desc')
            ->get($columns);
    }

    /**
     * Obtener logs de errores
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<CommandLog>
     */
    public function getErrors(array $columns = ['*']): Collection
    {
        return $this->getByStatus('error', $columns);
    }

    /**
     * Obtener logs exitosos
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<CommandLog>
     */
    public function getSuccessful(array $columns = ['*']): Collection
    {
        return $this->getByStatus('success', $columns);
    }

    /**
     * Obtener logs recientes (últimos N registros)
     *
     * @param int $limit Cantidad de registros
     * @param array $columns Columnas a seleccionar
     * @return Collection<CommandLog>
     */
    public function getRecent(int $limit = 50, array $columns = ['*']): Collection
    {
        return $this->model->orderBy('executed_at', 'desc')
            ->limit($limit)
            ->get($columns);
    }

    /**
     * Obtener logs por rango de fechas
     *
     * @param string $startDate Fecha de inicio
     * @param string $endDate Fecha de fin
     * @param array $columns Columnas a seleccionar
     * @return Collection<CommandLog>
     */
    public function getByDateRange(string $startDate, string $endDate, array $columns = ['*']): Collection
    {
        return $this->model->whereBetween('executed_at', [$startDate, $endDate])
            ->orderBy('executed_at', 'desc')
            ->get($columns);
    }

    /**
     * Contar comandos por estado
     *
     * @return array Array con conteo por estado
     */
    public function getCountByStatus(): array
    {
        $counts = $this->model->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'success' => $counts['success'] ?? 0,
            'error' => $counts['error'] ?? 0,
            'total' => array_sum($counts)
        ];
    }

    /**
     * Limpiar logs antiguos
     *
     * @param int $days Días de antigüedad
     * @return int Número de registros eliminados
     */
    public function cleanOldLogs(int $days = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($days);

        return $this->model->where('executed_at', '<', $cutoffDate)->delete();
    }

    /**
     * Obtener últimos comandos únicos ejecutados
     *
     * @param int $limit Límite de comandos únicos
     * @param array $columns Columnas a seleccionar
     * @return Collection<CommandLog>
     */
    public function getRecentUniqueCommands(int $limit = 20, array $columns = ['*']): Collection
    {
        return $this->model->select($columns)
            ->orderBy('executed_at', 'desc')
            ->groupBy('command')
            ->limit($limit)
            ->get();
    }

    /**
     * Buscar en output de comandos
     *
     * @param string $searchTerm Término de búsqueda
     * @param array $columns Columnas a seleccionar
     * @return Collection<CommandLog>
     */
    public function searchInOutput(string $searchTerm, array $columns = ['*']): Collection
    {
        return $this->model->where('output', 'like', "%{$searchTerm}%")
            ->orderBy('executed_at', 'desc')
            ->get($columns);
    }
}
