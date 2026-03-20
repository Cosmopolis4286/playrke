<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AchievementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AchievementController extends Controller
{
    public function __construct(
        private AchievementService $achievementService
    ) {}

    /**
     * Obtener todos los logros disponibles
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $achievements = $this->achievementService->getAllAchievementsWithProgress($user);

        return response()->json([
            'success' => true,
            'data' => $achievements
        ]);
    }

    /**
     * Obtener logros del usuario
     */
    public function getUserAchievements(): JsonResponse
    {
        $user = Auth::user();
        $userAchievements = $this->achievementService->getUserAchievements($user);

        return response()->json([
            'success' => true,
            'data' => $userAchievements
        ]);
    }

    /**
     * Obtener logros desbloqueados del usuario
     */
    public function getUnlockedAchievements(): JsonResponse
    {
        $user = Auth::user();
        $unlockedAchievements = $this->achievementService->getUnlockedAchievements($user);

        return response()->json([
            'success' => true,
            'data' => $unlockedAchievements
        ]);
    }

    /**
     * Obtener logros no vistos del usuario
     */
    public function getUnseenAchievements(): JsonResponse
    {
        $user = Auth::user();
        $unseenAchievements = $this->achievementService->getUnseenAchievements($user);

        return response()->json([
            'success' => true,
            'data' => $unseenAchievements
        ]);
    }

    /**
     * Marcar logros como vistos
     */
    public function markAsSeen(Request $request): JsonResponse
    {
        $request->validate([
            'achievement_ids' => 'nullable|array',
            'achievement_ids.*' => 'string|uuid',
        ]);

        $user = Auth::user();
        $achievementIds = $request->achievement_ids;

        $markedCount = $this->achievementService->markAchievementsAsSeen($user, $achievementIds);

        return response()->json([
            'success' => true,
            'message' => 'Achievements marked as seen',
            'data' => [
                'marked_count' => $markedCount
            ]
        ]);
    }

    /**
     * Marcar todos los logros como vistos
     */
    public function markAllAsSeen(): JsonResponse
    {
        $user = Auth::user();
        $markedCount = $this->achievementService->markAllAchievementsAsSeen($user);

        return response()->json([
            'success' => true,
            'message' => 'All achievements marked as seen',
            'data' => [
                'marked_count' => $markedCount
            ]
        ]);
    }

    /**
     * Obtener estadísticas de logros del usuario
     */
    public function getStats(): JsonResponse
    {
        $user = Auth::user();
        $stats = $this->achievementService->getUserAchievementStats($user);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Verificar y desbloquear logros automáticamente
     */
    public function checkAndUnlock(Request $request): JsonResponse
    {
        $request->validate([
            'context' => 'nullable|array',
        ]);

        $user = Auth::user();
        $context = $request->context ?? [];
        
        $unlockedAchievements = $this->achievementService->checkAndUnlockAchievements($user, $context);

        return response()->json([
            'success' => true,
            'message' => 'Achievements checked',
            'data' => [
                'unlocked_count' => count($unlockedAchievements),
                'unlocked_achievements' => $unlockedAchievements
            ]
        ]);
    }

    /**
     * Obtener logros por categoría
     */
    public function getByCategory(string $category): JsonResponse
    {
        $user = Auth::user();
        $achievements = $this->achievementService->getAchievementsByCategory($user, $category);

        return response()->json([
            'success' => true,
            'data' => $achievements
        ]);
    }
}
