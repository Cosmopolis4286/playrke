<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Services\Audit\SystemLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SystemLogController extends Controller
{
    private const LIST_FILTER_KEYS = ['level', 'context', 'date_from', 'date_to', 'search'];

    public function __construct(
        private SystemLogService $service
    ) {}

    public function index(Request $request): Response
    {
        return $this->handleServiceInertia(function () {
            return Inertia::render('SystemLogs/Index', [
                'logs'     => $this->service->getTableData(),
                'stats'    => $this->service->stats(),
                'contexts' => $this->service->contexts(),
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

    public function show(Request $request, string $id): JsonResponse
    {
        return $this->handleServiceJson(function () use ($id) {
            return ['data' => $this->service->show($id)];
        });
    }

    public function toggleReviewed(Request $request, string $id): JsonResponse
    {
        return $this->handleServiceJson(function () use ($id) {
            return ['data' => $this->service->toggleReviewed($id)];
        });
    }

    /**
     * Exporta a CSV todos los logs que coincidan con los filtros actuales
     * (sin límite de paginación) — mismo patrón que AuditController::export().
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(self::LIST_FILTER_KEYS);

        return response()->streamDownload(function () use ($filters) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Fecha', 'Nivel', 'Mensaje', 'Contexto', 'Ocurrencias', 'Revisado']);

            foreach ($this->service->exportCursor($filters) as $log) {
                fputcsv($handle, [
                    ($log->last_seen_at ?? $log->created_at)?->format('d/m/Y H:i:s'),
                    $log->level,
                    $log->message,
                    $log->context,
                    $log->occurrences,
                    $log->reviewed_at ? 'Sí' : 'No',
                ]);
            }

            fclose($handle);
        }, 'logs-sistema-' . now()->format('Y-m-d-His') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Cuenta cuántos logs nuevos hay desde $since — usado por el polling del
     * frontend (banner "hay N errores nuevos") sin repedir la tabla completa.
     */
    public function checkNew(Request $request): JsonResponse
    {
        return $this->handleServiceJson(function () use ($request) {
            $since = (string) $request->get('since');

            return ['data' => ['count' => $since !== '' ? $this->service->countSince($since) : 0]];
        });
    }
}
