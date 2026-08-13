<?php

namespace App\Services\Audit;

use App\Models\Audit;

class AuditService
{
    private const PAGE_SIZE = 50;

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
    public function fetchList(array $filters = [], int $page = 1): array
    {
        $query = $this->buildQuery($filters)->latest('audits.created_at');
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
     * Opciones de filtros disponibles para el frontend
     */
    public function filterOptions(): array
    {
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
    }

    // -------------------------------------------------------------------------

    private function buildQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $query = Audit::query()
            ->with('user:id,first_name,last_name,email')
            ->select('audits.*');

        if (!empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        if (!empty($filters['actor_type'])) {
            $query->where('actor_type', $filters['actor_type']);
        }

        if (!empty($filters['entity'])) {
            $query->where('auditable_type', $filters['entity']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        /**
         * Filtra por el SUJETO auditado (auditable_id), no por el actor que
         * ejecutó la acción (user_id). Usado para "ver historial de esta
         * entidad" — ej. deep-link desde /admin/users hacia el historial de
         * cambios de un usuario puntual. Requiere 'entity' para acotar el
         * tipo, ya que auditable_id no es único entre distintos modelos.
         */
        if (!empty($filters['auditable_id']) && !empty($filters['entity'])) {
            $query->where('auditable_id', $filters['auditable_id']);
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
