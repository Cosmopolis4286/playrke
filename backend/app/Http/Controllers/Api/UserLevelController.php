<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\User\UserLevelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Solo lectura del nivel/XP del propio usuario. Otorgar/quitar XP o
 * reiniciar el nivel NO vive aquí — son operaciones sensibles que solo
 * deben depender de progreso real de juego (BingoDrawService,
 * GameEngineService, GameCompletionService, listeners de referidos) o de
 * una acción administrativa auditada (AdminUserService::grantXp, que
 * además exige rol player). Antes existían addXp/removeXp/resetLevel/
 * getLeaderboard/calculateXpReward como endpoints self-service sin
 * restricción de rol ni límite acumulado, explotables por cualquier
 * usuario autenticado para inflar su propio XP/nivel — se eliminaron por
 * no tener ningún consumidor real en el frontend.
 */
class UserLevelController extends Controller
{
    public function __construct(
        private UserLevelService $userLevelService,
    ) {
    }

    public function getUserLevel(Request $request) : JsonResponse
    {
        $user = $request->user();
        $levelInfo = $this->userLevelService->getUserLevelInfo($user);

        return response()->json([
            'success' => true,
            'data' => $levelInfo,
        ]);
    }
}
