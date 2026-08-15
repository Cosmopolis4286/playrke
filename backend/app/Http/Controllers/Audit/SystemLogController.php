<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Services\Audit\SystemLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
}
