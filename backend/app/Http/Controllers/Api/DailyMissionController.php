<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Games\DailyMissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DailyMissionController extends Controller
{
    public function __construct(
        private readonly DailyMissionService $dailyMissionService,
    ) {
    }

    /**
     * Estado de las misiones diarias del usuario autenticado, para hoy.
     */
    public function today() : JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $status = $this->dailyMissionService->todayStatus($user)
            ->map(fn (array $row) => [
                'code' => $row['mission']->code,
                'name' => $row['mission']->name,
                'description' => $row['mission']->description,
                'progress' => $row['progress'],
                'target' => $row['target'],
                'xp_reward' => $row['mission']->xp_reward,
                'completed' => $row['completed'],
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }
}
