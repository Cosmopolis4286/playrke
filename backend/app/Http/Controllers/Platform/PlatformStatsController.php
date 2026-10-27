<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\PlatformStatsService;
use Illuminate\Http\JsonResponse;

/**
 * Métricas públicas de la plataforma (prueba social para la landing).
 */
class PlatformStatsController extends Controller
{
    public function __construct(
        private readonly PlatformStatsService $platformStats,
    ) {
    }

    public function index() : JsonResponse
    {
        return $this->handleServiceJson(fn () => $this->platformStats->summary());
    }
}
