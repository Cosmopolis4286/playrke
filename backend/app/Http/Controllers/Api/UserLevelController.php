<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\User\UserLevelService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserLevelController extends Controller
{
    public function __construct(
        private UserLevelService $userLevelService
    ) {}

    public function getUserLevel(Request $request): JsonResponse
    {
        $user = $request->user();
        $levelInfo = $this->userLevelService->getUserLevelInfo($user);

        return response()->json([
            'success' => true,
            'data' => $levelInfo,
        ]);
    }

    public function addXp(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $result = $this->userLevelService->addXpToUser(
            $user,
            $request->amount,
            $request->reason ?? 'Manual XP addition'
        );

        return response()->json($result);
    }

    public function removeXp(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $result = $this->userLevelService->removeXpFromUser(
            $user,
            $request->amount,
            $request->reason ?? 'Manual XP removal'
        );

        return response()->json($result);
    }

    public function getLeaderboard(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
            'sort_by' => 'nullable|in:total_xp,level,current_xp',
        ]);

        $limit = $request->limit ?? 10;
        $sortBy = $request->sort_by ?? 'total_xp';

        $leaderboard = $this->userLevelService->getLeaderboard($limit, $sortBy);

        return response()->json([
            'success' => true,
            'data' => [
                'leaderboard' => $leaderboard,
                'limit' => $limit,
                'sort_by' => $sortBy,
            ],
        ]);
    }

    public function calculateXpReward(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|string|max:50',
            'context' => 'nullable|array',
            'context.multiplier' => 'nullable|numeric|min:0.1|max:10',
            'context.bonus' => 'nullable|integer|min:0',
        ]);

        $reward = $this->userLevelService->calculateXpReward(
            $request->action,
            $request->context ?? []
        );

        return response()->json([
            'success' => true,
            'data' => [
                'action' => $request->action,
                'context' => $request->context ?? [],
                'xp_reward' => $reward,
            ],
        ]);
    }

    public function resetLevel(Request $request): JsonResponse
    {
        $user = $request->user();
        $result = $this->userLevelService->resetUserLevel($user);

        return response()->json($result);
    }
}
