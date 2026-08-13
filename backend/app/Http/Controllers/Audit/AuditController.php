<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditController extends Controller
{
    public function __construct(
        private AuditService $service
    ) {}

    public function index(Request $request): Response
    {
        return $this->handleServiceInertia(function () use ($request) {
            $filters       = $request->only(['entity', 'auditable_id']);
            $tableData     = $this->service->getTableData($filters);
            $filterOptions = $this->service->filterOptions();

            return Inertia::render('Audit/Index', [
                'audits'         => $tableData,
                'filterOptions'  => $filterOptions,
                'initialFilters' => $filters,
            ]);
        });
    }

    public function fetchList(Request $request): JsonResponse
    {
        return $this->handleServiceJson(function () use ($request) {
            $filters = $request->only(['event', 'actor_type', 'entity', 'user_id', 'auditable_id', 'date_from', 'date_to']);
            $page    = (int) $request->get('page', 1);

            return ['data' => $this->service->fetchList($filters, $page)];
        });
    }

    public function show(Request $request, string $id): JsonResponse
    {
        return $this->handleServiceJson(function () use ($id) {
            return ['data' => $this->service->show((int) $id)];
        });
    }
}
