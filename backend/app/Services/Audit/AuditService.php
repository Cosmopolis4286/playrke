<?php

namespace App\Services\Audit;

use App\Models\Audit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\LazyCollection;

class AuditService
{
    private const PAGE_SIZE = 50;

    /**
     * Columnas ordenables por el usuario — deben ser columnas reales de la
     * tabla `audits`, no valores computados en toRow() (actor/entity_id).
     */
    private const SORTABLE_COLUMNS = ['id', 'created_at', 'actor_type', 'event', 'auditable_type'];

    /**
     * Columnas para la tabla del frontend
     */
    private function columns(): array
    {
        return [
            ['key' => 'id',             'label' => '#',           'sortable' => true],
            ['key' => 'date',           'label' => 'Fecha',       'sortable' => true],
            ['key' => 'actor',          'label' => 'Actor',       'sortable' => false],
            ['key' => 'actor_type',     'label' => 'Tipo Actor',  'sortable' => true],
            ['key' => 'event',          'label' => 'Evento',      'sortable' => true],
            ['key' => 'entity',         'label' => 'Entidad',     'sortable' => true],
            ['key' => 'entity_id',      'label' => 'ID Entidad',  'sortable' => false],
            ['key' => 'ip_address',     'label' => 'IP',          'sortable' => false],
        ];
    }

    /**
     * Datos de tabla para Inertia (primera carga — últimos 50 registros)
     */
    public function getTableData(array $filters = []): array
    {
        $rows = $this->buildQuery($filters)
            ->latest('audits.created_at')
            ->limit(self::PAGE_SIZE)
            ->get()
            ->map(fn (Audit $a) => $this->toRow($a))
            ->values()
            ->toArray();

        return [
            'columns' => $this->columns(),
            'rows'    => $rows,
        ];
    }

    /**
     * Lista paginada para fetch-list (AJAX)
     */
    public function fetchList(array $filters = [], int $page = 1, ?string $sort = null, string $direction = 'desc'): array
    {
        $query = $this->applySort($this->buildQuery($filters), $sort, $direction);
        $total = (clone $query)->count();

        $rows = $query
            ->forPage($page, self::PAGE_SIZE)
            ->get()
            ->map(fn (Audit $a) => $this->toRow($a))
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

    /**
     * Cursor sobre TODOS los resultados que coincidan con los filtros (sin
     * paginar) — usado por AuditController::export() para generar el CSV
     * completo sin materializar la colección entera en memoria.
     *
     * @return LazyCollection<int, Audit>
     */
    public function exportCursor(array $filters = []): LazyCollection
    {
        return $this->buildQuery($filters)->latest('audits.created_at')->cursor();
    }

    private function applySort(\Illuminate\Database\Eloquent\Builder $query, ?string $sort, string $direction): \Illuminate\Database\Eloquent\Builder
    {
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        if ($sort === null || !in_array($sort, self::SORTABLE_COLUMNS, true)) {
            return $query->orderBy('audits.created_at', 'desc');
        }

        $column = $sort === 'id' || $sort === 'created_at' ? "audits.{$sort}" : $sort;

        return $query->orderBy($column, $direction);
    }

    /**
     * Detalle de un registro de auditoría
     */
    public function show(int $id): array
    {
        $audit = Audit::with('user')->findOrFail($id);

        return [
            'id'             => $audit->id,
            'date'           => $audit->created_at?->format('d/m/Y H:i:s'),
            'actor'          => $audit->user?->full_name ?? 'Sistema',
            'actor_type'     => $audit->actor_type,
            'event'          => $audit->event,
            'entity'         => $audit->auditable_name,
            'entity_id'      => $audit->auditable_id,
            'old_values'     => $audit->old_values,
            'new_values'     => $audit->new_values,
            'url'            => $audit->url,
            'ip_address'     => $audit->ip_address,
            'user_agent'     => $audit->user_agent,
        ];
    }

    /**
     * Opciones de filtros disponibles para el frontend.
     *
     * Cacheado 10 min: son 3 SELECT DISTINCT sobre toda la tabla `audits`,
     * que de otro modo corren en cada carga de /audit sin necesidad — los
     * eventos/tipos/entidades auditables cambian con muy baja frecuencia.
     */
    public function filterOptions(): array
    {
        return Cache::remember('audit:filter-options', 600, function (): array {
            $events = Audit::select('event')
                ->distinct()
                ->orderBy('event')
                ->pluck('event')
                ->map(fn ($e) => ['label' => ucfirst($e), 'value' => $e])
                ->values()
                ->toArray();

            $actorTypes = Audit::select('actor_type')
                ->distinct()
                ->orderBy('actor_type')
                ->pluck('actor_type')
                ->map(fn ($t) => ['label' => ucfirst($t), 'value' => $t])
                ->values()
                ->toArray();

            $entities = Audit::select('auditable_type')
                ->distinct()
                ->orderBy('auditable_type')
                ->pluck('auditable_type')
                ->map(function ($fqcn) {
                    $parts = explode('\\', $fqcn);
                    return ['label' => end($parts), 'value' => $fqcn];
                })
                ->values()
                ->toArray();

            return compact('events', 'actorTypes', 'entities');
        });
    }

    /**
     * Estadísticas para los stat tiles del frontend: total de registros y
     * top 3 entidades más auditadas. Cacheado igual que filterOptions() —
     * no necesita recalcularse en cada filtro/página, solo al cargar /audit.
     */
    public function stats(): array
    {
        return Cache::remember('audit:stats', 600, function (): array {
            $total = Audit::count();

            $topEntities = Audit::selectRaw('auditable_type, COUNT(*) as total')
                ->groupBy('auditable_type')
                ->orderByDesc('total')
                ->limit(3)
                ->get()
                ->map(fn ($row) => [
                    'entity' => class_basename($row->auditable_type),
                    'count'  => (int) $row->total,
                ])
                ->values()
                ->toArray();

            return compact('total', 'topEntities');
        });
    }

    // -------------------------------------------------------------------------

    private function buildQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $query = Audit::query()
            ->with('user:id,first_name,last_name,email')
            ->select('audits.*');

        if (!empty($filters['event'])) {
            $query->ofEvent($filters['event']);
        }

        if (!empty($filters['actor_type'])) {
            $query->ofActor($filters['actor_type']);
        }

        if (!empty($filters['user_id'])) {
            $query->forUser($filters['user_id']);
        }

        /**
         * Filtra por el SUJETO auditado (auditable_type + auditable_id), no
         * por el actor que ejecutó la acción (user_id). Usado para "ver
         * historial de esta entidad" — ej. deep-link desde /admin/users
         * hacia el historial de cambios de un usuario puntual. Requiere
         * 'entity' para acotar el tipo, ya que auditable_id no es único
         * entre distintos modelos. ofAuditable() compara con JSON_UNQUOTE
         * porque auditable_id se persiste como JSON (ver Audit::scopeOfAuditable).
         */
        if (!empty($filters['auditable_id']) && !empty($filters['entity'])) {
            $query->ofAuditable($filters['entity'], $filters['auditable_id']);
        } elseif (!empty($filters['entity'])) {
            $query->ofModel($filters['entity']);
        }

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($term) {
                $q->where('event', 'LIKE', "%{$term}%")
                    ->orWhere('actor_type', 'LIKE', "%{$term}%")
                    ->orWhere('auditable_type', 'LIKE', "%{$term}%")
                    ->orWhere('ip_address', 'LIKE', "%{$term}%")
                    // Búsqueda dentro del contenido de los cambios (ej. "¿quién
                    // cambió el email a X?") — LIKE sobre columnas JSON, sin
                    // índice dedicado; aceptable dado el volumen de /audit y
                    // que ya se acota primero por los demás filtros/fecha.
                    ->orWhere('old_values', 'LIKE', "%{$term}%")
                    ->orWhere('new_values', 'LIKE', "%{$term}%");
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('audits.created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('audits.created_at', '<=', $filters['date_to']);
        }

        return $query;
    }

    private function toRow(Audit $audit): array
    {
        return [
            'id'         => (string) $audit->id,
            'date'       => $audit->created_at?->format('d/m/Y H:i:s') ?? '—',
            'actor'      => $audit->user?->full_name ?? 'Sistema',
            'actor_type' => $audit->actor_type,
            'event'      => $audit->event,
            'entity'     => $audit->auditable_name,
            'entity_id'  => is_array($audit->auditable_id)
                ? implode(' / ', $audit->auditable_id)
                : (string) ($audit->auditable_id ?? '—'),
            'ip_address' => $audit->ip_address ?? '—',
        ];
    }
}
