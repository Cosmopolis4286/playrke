<?php

namespace App\Services\Audit;

use App\Models\SystemLog;
use Illuminate\Support\Facades\Cache;

class SystemLogService
{
    private const PAGE_SIZE = 100;

    private const SORTABLE_COLUMNS = ['id', 'created_at', 'level'];

    private function columns(): array
    {
        return [
            ['key' => 'id',      'label' => '#',        'sortable' => true],
            ['key' => 'date',    'label' => 'Fecha',    'sortable' => true],
            ['key' => 'level',   'label' => 'Nivel',     'sortable' => true],
            ['key' => 'message', 'label' => 'Mensaje',   'sortable' => false],
            ['key' => 'context', 'label' => 'Contexto',  'sortable' => false],
        ];
    }

    public function getTableData(array $filters = []): array
    {
        $rows = $this->buildQuery($filters)
            ->latest('system_logs.created_at')
            ->limit(self::PAGE_SIZE)
            ->get()
            ->map(fn (SystemLog $log) => $this->toRow($log))
            ->values()
            ->toArray();

        return [
            'columns' => $this->columns(),
            'rows'    => $rows,
        ];
    }

    public function fetchList(array $filters = [], int $page = 1, ?string $sort = null, string $direction = 'desc'): array
    {
        $query = $this->applySort($this->buildQuery($filters), $sort, $direction);
        $total = (clone $query)->count();

        $rows = $query
            ->forPage($page, self::PAGE_SIZE)
            ->get()
            ->map(fn (SystemLog $log) => $this->toRow($log))
            ->values()
            ->toArray();

        return [
            'columns'      => $this->columns(),
            'rows'         => $rows,
            'total'        => $total,
            'per_page'     => self::PAGE_SIZE,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / self::PAGE_SIZE),
        ];
    }

    public function show(string $id): array
    {
        $log = SystemLog::findOrFail($id);

        return [
            'id'      => $log->id,
            'date'    => $log->created_at?->format('d/m/Y H:i:s'),
            'level'   => $log->level,
            'message' => $log->message,
            'context' => $log->context,
            'stack'   => $log->stack,
            'meta'    => $log->meta,
        ];
    }

    /**
     * Estadísticas para los stat tiles: total + conteo por nivel.
     * Cacheado 5 min — menor TTL que /audit porque errores nuevos son más
     * urgentes de reflejar que cambios de auditoría de negocio.
     */
    public function stats(): array
    {
        return Cache::remember('system-logs:stats', 300, function (): array {
            $total = SystemLog::count();

            $byLevel = SystemLog::selectRaw('level, COUNT(*) as total')
                ->groupBy('level')
                ->get()
                ->map(fn ($row) => ['level' => $row->level, 'count' => (int) $row->total])
                ->values()
                ->toArray();

            return compact('total', 'byLevel');
        });
    }

    private function applySort(\Illuminate\Database\Eloquent\Builder $query, ?string $sort, string $direction): \Illuminate\Database\Eloquent\Builder
    {
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        if ($sort === null || !in_array($sort, self::SORTABLE_COLUMNS, true)) {
            return $query->orderBy('system_logs.created_at', 'desc');
        }

        $column = $sort === 'id' || $sort === 'created_at' ? "system_logs.{$sort}" : $sort;

        return $query->orderBy($column, $direction);
    }

    private function buildQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $query = SystemLog::query()->select('system_logs.*');

        if (!empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($term) {
                $q->where('message', 'LIKE', "%{$term}%")
                    ->orWhere('context', 'LIKE', "%{$term}%");
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('system_logs.created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('system_logs.created_at', '<=', $filters['date_to']);
        }

        return $query;
    }

    private function toRow(SystemLog $log): array
    {
        return [
            'id'      => (string) $log->id,
            'date'    => $log->created_at?->format('d/m/Y H:i:s') ?? '—',
            'level'   => $log->level,
            'message' => $log->message,
            'context' => $log->context ?? '—',
        ];
    }
}
