<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditController extends Controller
{
    private const LIST_FILTER_KEYS = ['event', 'actor_type', 'entity', 'user_id', 'auditable_id', 'date_from', 'date_to', 'search'];

    public function __construct(
        private AuditService $service
    ) {}

    public function index(Request $request): Response
    {
        return $this->handleServiceInertia(function () use ($request) {
            $filters       = $request->only(['entity', 'auditable_id']);
            $tableData     = $this->service->getTableData($filters);
            $filterOptions = $this->service->filterOptions();
            $stats         = $this->service->stats();

            return Inertia::render('Audit/Index', [
                'audits'         => $tableData,
                'filterOptions'  => $filterOptions,
                'initialFilters' => $filters,
                'stats'          => $stats,
            ]);
        });
    }

    public function fetchList(Request $request): JsonResponse
    {
        return $this->handleServiceJson(function () use ($request) {
            $filters   = $request->only(self::LIST_FILTER_KEYS);
            $page      = (int) $request->get('page', 1);
            $sort      = $request->get('sort');
            $direction = (string) $request->get('direction', 'desc');

            return ['data' => $this->service->fetchList($filters, $page, $sort, $direction)];
        });
    }

    /**
     * Exporta a CSV todos los registros que coincidan con los filtros
     * actuales (sin límite de paginación) — usa cursor() vía el service
     * para no materializar el resultado completo en memoria.
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(self::LIST_FILTER_KEYS);

        return response()->streamDownload(function () use ($filters) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Fecha', 'Actor', 'Tipo Actor', 'Evento', 'Entidad', 'ID Entidad', 'IP', 'Valores anteriores', 'Valores nuevos']);

            foreach ($this->service->exportCursor($filters) as $audit) {
                fputcsv($handle, [
                    $audit->created_at?->format('d/m/Y H:i:s'),
                    $audit->user?->full_name ?? 'Sistema',
                    $audit->actor_type,
                    $audit->event,
                    $audit->auditable_name,
                    is_array($audit->auditable_id) ? implode(' / ', $audit->auditable_id) : $audit->auditable_id,
                    $audit->ip_address,
                    $audit->old_values ? json_encode($audit->old_values) : null,
                    $audit->new_values ? json_encode($audit->new_values) : null,
                ]);
            }

            fclose($handle);
        }, 'auditoria-' . now()->format('Y-m-d-His') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        return $this->handleServiceJson(function () use ($id) {
            return ['data' => $this->service->show((int) $id)];
        });
    }
}
