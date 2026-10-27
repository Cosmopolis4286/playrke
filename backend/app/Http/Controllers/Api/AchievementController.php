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
        private AchievementService $achievementService,
    ) {
    }

    /**
     * Verificar y desbloquear logros automáticamente.
     *
     * Sin gate de rol a propósito: todos los requisitos evaluados en
     * AchievementService::shouldUnlockAchievement() comparan contra columnas/
     * relaciones reales del usuario (games_played_count, total_winnings,
     * login_streak_days, getCurrentLevel(), referrals()->count()) — el
     * $context recibido aquí no puede satisfacer ninguno de los casos
     * 'custom' (early_bird/night_owl exigen $context['at'] instanceof
     * Carbon, que un array validado desde HTTP nunca produce; solo llega
     * así cuando GameCompletionService dispara el chequeo internamente
     * tras una partida real). Un usuario no-player que llame este endpoint
     * simplemente no desbloqueará nada — no hay vector de abuso.
     */
    public function checkAndUnlock(Request $request) : JsonResponse
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
                'unlocked_achievements' => $unlockedAchievements,
            ],
        ]);
    }

    /**
     * Obtener logros por categoría
     */
    public function getByCategory(string $category) : JsonResponse
    {
        $user = Auth::user();
        $achievements = $this->achievementService->getAchievementsByCategory($user, $category);

        return response()->json([
            'success' => true,
            'data' => $achievements,
        ]);
    }

    /**
     * Obtener estadísticas de logros del usuario
     */
    public function getStats() : JsonResponse
    {
        $user = Auth::user();
        $stats = $this->achievementService->getUserAchievementStats($user);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Obtener logros desbloqueados del usuario
     */
    public function getUnlockedAchievements() : JsonResponse
    {
        $user = Auth::user();
        $unlockedAchievements = $this->achievementService->getUnlockedAchievements($user);

        return response()->json([
            'success' => true,
            'data' => $unlockedAchievements,
        ]);
    }

    /**
     * Obtener logros no vistos del usuario
     */
    public function getUnseenAchievements() : JsonResponse
    {
        $user = Auth::user();
        $unseenAchievements = $this->achievementService->getUnseenAchievements($user);

        return response()->json([
            'success' => true,
            'data' => $unseenAchievements,
        ]);
    }

    /**
     * Obtener logros del usuario
     */
    public function getUserAchievements() : JsonResponse
    {
        $user = Auth::user();
        $userAchievements = $this->achievementService->getUserAchievements($user);

        return response()->json([
            'success' => true,
            'data' => $userAchievements,
        ]);
    }

    /**
     * Obtener todos los logros disponibles
     */
    public function index() : JsonResponse
    {
        $user = Auth::user();
        $achievements = $this->achievementService->getAllAchievementsWithProgress($user);

        return response()->json([
            'success' => true,
            'data' => $achievements,
        ]);
    }

    /**
     * Marcar todos los logros como vistos
     */
    public function markAllAsSeen() : JsonResponse
    {
        $user = Auth::user();
        $markedCount = $this->achievementService->markAllAchievementsAsSeen($user);

        return response()->json([
            'success' => true,
            'message' => 'All achievements marked as seen',
            'data' => [
                'marked_count' => $markedCount,
            ],
        ]);
    }

    /**
     * Marcar logros como vistos
     */
    public function markAsSeen(Request $request) : JsonResponse
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
                'marked_count' => $markedCount,
            ],
        ]);
    }
}
