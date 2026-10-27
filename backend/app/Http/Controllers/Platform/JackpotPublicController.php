<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Jackpot\JackpotPublicStatsService;
use Illuminate\Http\JsonResponse;

/**
 * Saldo público de los jackpots activos (prueba social de landing/vitrina).
 */
class JackpotPublicController extends Controller
{
    public function __construct(
        private readonly JackpotPublicStatsService $jackpotStats,
    ) {
    }

    public function index() : JsonResponse
    {
        return $this->handleServiceJson(fn () => ['jackpots' => $this->jackpotStats->list()]);
    }
}
