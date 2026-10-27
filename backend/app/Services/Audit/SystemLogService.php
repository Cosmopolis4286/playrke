<?php

namespace App\Services\Audit;

use App\Models\SystemLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\LazyCollection;

class SystemLogService
{
    private const PAGE_SIZE = 100;
    private const RELATED_LIMIT = 10;

    /**
     * Ventana de tiempo (antes/después de last_seen_at) para buscar otros
     * logs del mismo usuario — suficientemente amplia para capturar la
     * secuencia de acciones que probablemente causaron o siguieron al
     * error, sin traer todo el historial del usuario.
     */
    private const RELATED_WINDOW_MINUTES = 10;

    /**
     * Columnas ordenables por el usuario, mapeadas a su columna real en BD
     * (ver applySort()) — 'date' no es una columna física, se muestra al
     * usuario pero internamente ordena por last_seen_at, que es lo que
     * realmente representa "última vez" en toRow().
     */
    private const SORTABLE_COLUMNS = ['id', 'date', 'level', 'occurrences'];

    /**
     * Asigna (o desasigna, con $userId null) un log a un admin — reparte la
     * carga cuando hay varios admins mirando /logs; el filtro "assigned_to
     * =mine" en buildQuery() deja ver solo lo propio.
     */
    public function assign(string $id, ?string $userId) : array
    {
        $log = SystemLog::findOrFail($id);
        $log->forceFill(['assigned_to_user_id' => $userId])->save();

        return $this->toDetail($log->fresh(['reviewedBy', 'ignoredBy', 'assignedTo']));
    }

    /**
     * Admins/superadmins disponibles para asignar un log — alimenta el
     * selector de "Asignar a" en el modal de detalle.
     */
    public function assignableUsers() : array
    {
        return Cache::remember('system-logs:assignable-users', 300, function() : array {
            return \App\Models\User::whereHas(
                'roles',
                fn ($q) => $q->whereIn('name', ['admin', 'superadmin']),
            )
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name'])
                ->map(fn ($u) => ['value' => (string) $u->id, 'label' => trim("{$u->first_name} {$u->last_name}")])
                ->values()
                ->toArray();
        });
    }

    /**
     * Contextos distintos ya vistos — alimenta el filtro dropdown de
     * "Contexto" en el frontend. Cacheado igual que audit filterOptions().
     */
    public function contexts() : array
    {
        return Cache::remember('system-logs:contexts', 300, function() : array {
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

    /**
     * Logs cuyo last_seen_at es posterior a $sinceIso — usado por el
     * auto-refresh del frontend (polling) para detectar errores nuevos sin
     * repedir la tabla completa. Solo cuenta + trae un preview corto, no
     * reemplaza fetchList(): el admin decide cuándo refrescar la vista real.
     */
    public function countSince(string $sinceIso) : int
    {
        return $this->excludeIgnored(SystemLog::where('last_seen_at', '>', $sinceIso))->count();
    }

    /**
     * Cursor sobre TODOS los logs que coincidan con los filtros (sin
     * paginar) — mismo patrón que AuditService::exportCursor(), evita
     * materializar el resultado completo en memoria para el CSV.
     *
     * @return LazyCollection<int, SystemLog>
     */
    public function exportCursor(array $filters = []) : LazyCollection
    {
        return $this->buildQuery($filters)->latest('system_logs.last_seen_at')->cursor();
    }

    public function fetchList(array $filters = [], int $page = 1, ?string $sort = null, string $direction = 'desc') : array
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
            'columns' => $this->columns(),
            'rows' => $rows,
            'total' => $total,
            'per_page' => self::PAGE_SIZE,
            'current_page' => $page,
            'last_page' => (int) ceil($total / self::PAGE_SIZE),
        ];
    }

    public function getTableData(array $filters = []) : array
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
            'rows' => $rows,
        ];
    }

    /**
     * Otros logs (error/warn) del mismo usuario en sesión, cerca en el
     * tiempo del log actual — da contexto de "qué más le pasó a este
     * usuario" sin necesidad de guardar un historial de cada ocurrencia
     * individual (ver docblock de la tabla: occurrences es solo un
     * contador). Requiere meta.user_id, que DatabaseLogHandler solo captura
     * si había un usuario autenticado en el momento del error.
     */
    public function relatedLogs(string $id) : array
    {
        $log = SystemLog::findOrFail($id);
        $userId = $log->meta['user_id'] ?? null;

        if ($userId === null) {
            return [];
        }

        $anchor = $log->last_seen_at ?? $log->created_at;

        return SystemLog::query()
            ->whereKeyNot($log->id)
            ->whereJsonContains('meta->user_id', $userId)
            ->whereBetween('last_seen_at', [
                $anchor->clone()->subMinutes(self::RELATED_WINDOW_MINUTES),
                $anchor->clone()->addMinutes(self::RELATED_WINDOW_MINUTES),
            ])
            ->orderBy('last_seen_at')
            ->limit(self::RELATED_LIMIT)
            ->get(['id', 'level', 'message', 'context', 'last_seen_at'])
            ->map(fn (SystemLog $related) => [
                'id' => (string) $related->id,
                'level' => $related->level,
                'message' => $related->message,
                'context' => $related->context,
                'date' => $related->last_seen_at?->toIso8601String(),
            ])
            ->values()
            ->toArray();
    }

    public function show(string $id) : array
    {
        $log = SystemLog::with(['reviewedBy', 'ignoredBy', 'assignedTo'])->findOrFail($id);

        return $this->toDetail($log);
    }

    /**
     * Estadísticas para los stat tiles: total + conteo por nivel, excluyendo
     * lo ignorado (ruido ya conocido no debe inflar los números que el admin
     * usa para priorizar), más el conteo de sin revisar/ignorados como
     * indicadores propios. Cacheado 5 min — menor TTL que /audit porque
     * errores nuevos son más urgentes de reflejar que cambios de auditoría
     * de negocio.
     */
    public function stats() : array
    {
        return Cache::remember('system-logs:stats', 300, function() : array {
            $base = fn () => $this->excludeIgnored(SystemLog::query());

            $total = $base()->count();

            $byLevel = $base()
                ->selectRaw('level, COUNT(*) as total')
                ->groupBy('level')
                ->get()
                ->map(fn ($row) => ['level' => $row->level, 'count' => (int) $row->total])
                ->values()
                ->toArray();

            $ignoredCount = SystemLog::whereNotNull('ignored_at')->count();

            // Sin revisar = no tiene reviewed_at Y no está ignorado (un log
            // ignorado ya fue triado, no debe seguir contando como pendiente).
            $unreviewedCount = $base()->whereNull('reviewed_at')->count();

            return compact('total', 'byLevel', 'ignoredCount', 'unreviewedCount');
        });
    }

    /**
     * Marca (o desmarca) un log como ignorado — "conocido, no molestar más"
     * a nivel de fingerprint, a diferencia de reviewed_at que es por fila.
     * Mientras esté ignorado, nuevas ocurrencias del mismo error no cuentan
     * en newLogsCount ni en las stats "sin revisar" (ver excludeIgnored()).
     */
    public function toggleIgnored(string $id) : array
    {
        $log = SystemLog::findOrFail($id);

        if ($log->ignored_at) {
            $log->forceFill(['ignored_at' => null, 'ignored_by_user_id' => null])->save();
        } else {
            $log->forceFill(['ignored_at' => now(), 'ignored_by_user_id' => Auth::id()])->save();
        }

        Cache::forget('system-logs:stats');

        return $this->toDetail($log->fresh(['reviewedBy', 'ignoredBy', 'assignedTo']));
    }

    /**
     * Marca (o desmarca) un log como revisado — distingue "ya lo vi, es
     * conocido" de "nuevo, sin revisar" cuando hay más de un admin mirando
     * /logs.
     */
    public function toggleReviewed(string $id) : array
    {
        $log = SystemLog::findOrFail($id);

        if ($log->reviewed_at) {
            $log->forceFill(['reviewed_at' => null, 'reviewed_by_user_id' => null])->save();
        } else {
            $log->forceFill(['reviewed_at' => now(), 'reviewed_by_user_id' => Auth::id()])->save();
        }

        Cache::forget('system-logs:stats');

        return $this->toDetail($log->fresh(['reviewedBy', 'ignoredBy', 'assignedTo']));
    }

    private function applySort(\Illuminate\Database\Eloquent\Builder $query, ?string $sort, string $direction) : \Illuminate\Database\Eloquent\Builder
    {
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        if ($sort === null || ! in_array($sort, self::SORTABLE_COLUMNS, true)) {
            return $query->orderBy('system_logs.last_seen_at', 'desc');
        }

        // 'date' (nombre visible en la tabla) no es columna física — el dato
        // que muestra toRow() para 'date' es last_seen_at.
        $realColumn = $sort === 'date' ? 'last_seen_at' : $sort;
        $column = in_array($sort, ['id', 'date', 'occurrences'], true) ? "system_logs.{$realColumn}" : $realColumn;

        return $query->orderBy($column, $direction);
    }

    private function buildQuery(array $filters) : \Illuminate\Database\Eloquent\Builder
    {
        $query = SystemLog::query()->select('system_logs.*');

        if (! empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        if (! empty($filters['context'])) {
            $query->where('context', $filters['context']);
        }

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function(\Illuminate\Database\Eloquent\Builder $q) use ($term) {
                $q->where('message', 'LIKE', "%{$term}%")
                    ->orWhere('context', 'LIKE', "%{$term}%");
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('system_logs.last_seen_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('system_logs.last_seen_at', '<=', $filters['date_to']);
        }

        // 'mine' filtra por el admin autenticado — atajo de "assigned_to_user_id"
        // sin exponer IDs de otros usuarios en la UI.
        if (! empty($filters['assigned_to_user_id'])) {
            $query->where('assigned_to_user_id', $filters['assigned_to_user_id'] === 'mine' ? Auth::id() : $filters['assigned_to_user_id']);
        }

        // Por defecto la vista principal oculta lo ignorado (ruido conocido);
        // 'ignored' = 'only' lo invierte para poder auditar qué se silenció.
        if (($filters['ignored'] ?? null) === 'only') {
            $query->whereNotNull('ignored_at');
        } elseif (($filters['ignored'] ?? null) !== 'all') {
            $this->excludeIgnored($query);
        }

        // 'reviewed' = 'no' filtra solo lo pendiente de revisar (click desde
        // el stat tile "Sin revisar"); 'yes' el complemento, para auditar
        // qué ya se marcó como visto.
        if (($filters['reviewed'] ?? null) === 'no') {
            $query->whereNull('reviewed_at');
        } elseif (($filters['reviewed'] ?? null) === 'yes') {
            $query->whereNotNull('reviewed_at');
        }

        return $query;
    }

    /**
     * Tabla compacta: 'level' incluye el indicador de revisado adosado, y
     * 'message' incluye contexto + ocurrencias como metadata secundaria —
     * ver #cell-level y #cell-message en SystemLogs/Index.vue. Las claves
     * 'context'/'occurrences'/'reviewed' siguen viajando en cada fila
     * (toRow()) para esos slots, solo dejan de ser columnas con su propio
     * encabezado.
     */
    private function columns() : array
    {
        return [
            ['key' => 'id',      'label' => '#',          'sortable' => true],
            ['key' => 'date',    'label' => 'Última vez', 'sortable' => true],
            ['key' => 'level',   'label' => 'Nivel',      'sortable' => true],
            ['key' => 'message', 'label' => 'Mensaje',    'sortable' => false],
        ];
    }

    /**
     * Logs ignorados no cuentan en las stats "sin revisar" ni en el conteo
     * de nuevos del polling — el admin decidió explícitamente que ese
     * fingerprint es conocido y no amerita más atención, a diferencia de
     * reviewed_at que es por fila y se resetea con cada nueva ocurrencia.
     */
    private function excludeIgnored(\Illuminate\Database\Eloquent\Builder $query) : \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNull('ignored_at');
    }

    private function toDetail(SystemLog $log) : array
    {
        return [
            'id' => $log->id,
            // ISO 8601 con offset (no un string ya formateado en la zona del
            // servidor) para que el frontend pueda convertirlo a la zona
            // horaria local del navegador del usuario y mostrar ambas.
            'date' => $log->created_at?->toIso8601String(),
            'last_seen_at' => $log->last_seen_at?->toIso8601String(),
            'level' => $log->level,
            'message' => $log->message,
            'context' => $log->context,
            'stack' => $log->stack,
            'meta' => $log->meta,
            'occurrences' => $log->occurrences,
            'reviewed_at' => $log->reviewed_at?->toIso8601String(),
            'reviewed_by' => $log->reviewedBy?->full_name,
            'ignored_at' => $log->ignored_at?->toIso8601String(),
            'ignored_by' => $log->ignoredBy?->full_name,
            'assigned_to_user_id' => $log->assigned_to_user_id ? (string) $log->assigned_to_user_id : null,
            'assigned_to' => $log->assignedTo?->full_name,
            'server_timezone' => config('app.timezone'),
        ];
    }

    private function toRow(SystemLog $log) : array
    {
        return [
            'id' => (string) $log->id,
            // ISO 8601 con offset (no un string ya formateado en la zona del
            // servidor) para que la tabla lo convierta a la zona horaria
            // local del navegador — ver useLocalDateTime.ts.
            'date' => ($log->last_seen_at ?? $log->created_at)?->toIso8601String(),
            'level' => $log->level,
            'message' => $log->message,
            'context' => $log->context ?? '—',
            'occurrences' => $log->occurrences,
            'reviewed' => (bool) $log->reviewed_at,
            'ignored' => (bool) $log->ignored_at,
            'assigned_to' => $log->assignedTo?->full_name,
            'server_timezone' => config('app.timezone'),
        ];
    }
}
