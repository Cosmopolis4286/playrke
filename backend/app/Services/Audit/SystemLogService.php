<?php

namespace App\Services\Audit;

use App\Models\SystemLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SystemLogService
{
    private const PAGE_SIZE = 100;

    private const SORTABLE_COLUMNS = ['id', 'created_at', 'level', 'occurrences'];

    private function columns(): array
    {
        return [
            ['key' => 'id',          'label' => '#',           'sortable' => true],
            ['key' => 'date',        'label' => 'Última vez',  'sortable' => true],
            ['key' => 'level',       'label' => 'Nivel',       'sortable' => true],
            ['key' => 'message',     'label' => 'Mensaje',     'sortable' => false],
            ['key' => 'context',     'label' => 'Contexto',    'sortable' => false],
            ['key' => 'occurrences', 'label' => 'Ocurrencias', 'sortable' => true],
            ['key' => 'reviewed',    'label' => 'Revisado',    'sortable' => false],
        ];
    }

    public function getTableData(array $filters = []): array
    {
        $rows = $this->buildQuery($filters)
            ->latest('system_logs.last_seen_at')
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
        $log = SystemLog::with('reviewedBy')->findOrFail($id);

        return $this->toDetail($log);
    }

    /**
     * Marca (o desmarca) un log como revisado — distingue "ya lo vi, es
     * conocido" de "nuevo, sin revisar" cuando hay más de un admin mirando
     * /logs.
     */
    public function toggleReviewed(string $id): array
    {
        $log = SystemLog::findOrFail($id);

        if ($log->reviewed_at) {
            $log->forceFill(['reviewed_at' => null, 'reviewed_by_user_id' => null])->save();
        } else {
            $log->forceFill(['reviewed_at' => now(), 'reviewed_by_user_id' => Auth::id()])->save();
        }

        return $this->toDetail($log->fresh('reviewedBy'));
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
            return $query->orderBy('system_logs.last_seen_at', 'desc');
        }

        $column = in_array($sort, ['id', 'created_at', 'occurrences'], true) ? "system_logs.{$sort}" : $sort;

        return $query->orderBy($column, $direction);
    }

    private function buildQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $query = SystemLog::query()->select('system_logs.*');

        if (!empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        if (!empty($filters['context'])) {
            $query->where('context', $filters['context']);
        }

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($term) {
                $q->where('message', 'LIKE', "%{$term}%")
                    ->orWhere('context', 'LIKE', "%{$term}%");
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('system_logs.last_seen_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('system_logs.last_seen_at', '<=', $filters['date_to']);
        }

        return $query;
    }

    /**
     * Contextos distintos ya vistos — alimenta el filtro dropdown de
     * "Contexto" en el frontend. Cacheado igual que audit filterOptions().
     */
    public function contexts(): array
    {
        return Cache::remember('system-logs:contexts', 300, function (): array {
            return SystemLog::query()
                ->whereNotNull('context')
                ->distinct()
                ->orderBy('context')
                ->pluck('context')
                ->map(fn ($c) => ['label' => $c, 'value' => $c])
                ->values()
                ->toArray();
        });
    }

    private function toRow(SystemLog $log): array
    {
        return [
            'id'          => (string) $log->id,
            'date'        => ($log->last_seen_at ?? $log->created_at)?->format('d/m/Y H:i:s') ?? '—',
            'level'       => $log->level,
            'message'     => $log->message,
            'context'     => $log->context ?? '—',
            'occurrences' => $log->occurrences,
            'reviewed'    => (bool) $log->reviewed_at,
        ];
    }

    private function toDetail(SystemLog $log): array
    {
        return [
            'id'           => $log->id,
            'date'         => $log->created_at?->format('d/m/Y H:i:s'),
            'last_seen_at' => $log->last_seen_at?->format('d/m/Y H:i:s'),
            'level'        => $log->level,
            'message'      => $log->message,
            'context'      => $log->context,
            'stack'        => $log->stack,
            'meta'         => $log->meta,
            'occurrences'  => $log->occurrences,
            'reviewed_at'  => $log->reviewed_at?->format('d/m/Y H:i:s'),
            'reviewed_by'  => $log->reviewedBy?->full_name,
        ];
    }
}
