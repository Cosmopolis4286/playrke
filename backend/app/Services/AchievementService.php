<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AchievementService
{
    /**
     * Obtener todos los logros con el progreso del usuario
     */
    public function getAllAchievementsWithProgress(User $user): array
    {
        $achievements = Achievement::active()
            ->orderByOrder()
            ->get();

        return $achievements->map(function ($achievement) use ($user) {
            $progress = $achievement->getProgressForUser($user);
            
            return [
                'id' => $achievement->id,
                'code' => $achievement->code,
                'name' => $achievement->name,
                'description' => $achievement->description,
                'icon' => $achievement->icon_url,
                'badge_color' => $achievement->badge_color,
                'badge_style' => $achievement->badge_style,
                'xp_reward' => $achievement->xp_reward,
                'category' => $achievement->category,
                'is_hidden' => $achievement->is_hidden,
                'requirements' => $achievement->requirements,
                'progress' => $progress,
                'is_unlocked' => $progress['is_unlocked'],
                'unlocked_at' => $progress['unlocked_at'],
            ];
        })->toArray();
    }

    /**
     * Obtener logros desbloqueados del usuario
     */
    public function getUserAchievements(User $user): array
    {
        $achievements = $user->achievements()
            ->withPivot(['unlocked_at', 'progress_data', 'is_seen'])
            ->orderBy('pivot_unlocked_at', 'desc')
            ->get();

        return $achievements->map(function ($achievement) {
            return [
                'id' => $achievement->id,
                'code' => $achievement->code,
                'name' => $achievement->name,
                'description' => $achievement->description,
                'icon' => $achievement->icon_url,
                'badge_color' => $achievement->badge_color,
                'badge_style' => $achievement->badge_style,
                'xp_reward' => $achievement->xp_reward,
                'category' => $achievement->category,
                'unlocked_at' => $achievement->pivot->unlocked_at,
                'is_seen' => $achievement->pivot->is_seen,
            ];
        })->toArray();
    }

    /**
     * Obtener logros no vistos del usuario
     */
    public function getUnseenAchievements(User $user): array
    {
        $achievements = $user->achievements()
            ->wherePivot('is_seen', false)
            ->orderBy('pivot_unlocked_at', 'desc')
            ->get();

        return $achievements->map(function ($achievement) {
            return [
                'id' => $achievement->id,
                'code' => $achievement->code,
                'name' => $achievement->name,
                'description' => $achievement->description,
                'icon' => $achievement->icon_url,
                'badge_color' => $achievement->badge_color,
                'badge_style' => $achievement->badge_style,
                'xp_reward' => $achievement->xp_reward,
                'category' => $achievement->category,
                'unlocked_at' => $achievement->pivot->unlocked_at,
            ];
        })->toArray();
    }

    /**
     * Marcar logros como vistos
     */
    public function markAchievementsAsSeen(User $user, ?array $achievementIds = null): int
    {
        $query = $user->achievements()->wherePivot('is_seen', false);

        if ($achievementIds) {
            $query->whereIn('achievements.id', $achievementIds);
        }

        return $query->newPivotStatement()->update(['is_seen' => true]);
    }

    /**
     * Marcar todos los logros como vistos
     */
    public function markAllAchievementsAsSeen(User $user): int
    {
        return $this->markAchievementsAsSeen($user);
    }

    /**
     * Obtener estadísticas de logros del usuario
     */
    public function getUserAchievementStats(User $user): array
    {
        $totalAchievements = Achievement::active()->count();
        $unlockedCount = $user->achievements()->count();
        $unseenCount = $user->achievements()->wherePivot('is_seen', false)->count();
        $totalXpFromAchievements = $user->achievements()->sum('xp_reward');

        // Obtener logros por categoría
        $categories = Achievement::active()
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        $unlockedByCategory = $user->achievements()
            ->selectRaw('category, COUNT(*) as unlocked')
            ->join('achievements', 'achievements.id', '=', 'user_achievements.achievement_id')
            ->groupBy('category')
            ->pluck('unlocked', 'category')
            ->toArray();

        $categoryStats = [];
        foreach ($categories as $category => $total) {
            $categoryStats[$category] = [
                'total' => $total,
                'unlocked' => $unlockedByCategory[$category] ?? 0,
                'percentage' => $total > 0 ? round((($unlockedByCategory[$category] ?? 0) / $total) * 100, 1) : 0,
            ];
        }

        return [
            'total_achievements' => $totalAchievements,
            'unlocked_count' => $unlockedCount,
            'unseen_count' => $unseenCount,
            'completion_percentage' => $totalAchievements > 0 ? round(($unlockedCount / $totalAchievements) * 100, 1) : 0,
            'total_xp_from_achievements' => $totalXpFromAchievements,
            'categories' => $categoryStats,
        ];
    }

    /**
     * Verificar y desbloquear logros automáticamente
     */
    public function checkAndUnlockAchievements(User $user, array $context = []): array
    {
        $unlockedAchievements = [];

        DB::beginTransaction();
        try {
            $achievements = Achievement::active()
                ->whereDoesntHave('users', function ($query) use ($user) {
                    $query->where('users.id', $user->id);
                })
                ->get();

            foreach ($achievements as $achievement) {
                if ($this->shouldUnlockAchievement($user, $achievement, $context)) {
                    $userAchievement = $achievement->unlockForUser($user);
                    $unlockedAchievements[] = [
                        'achievement' => $achievement,
                        'user_achievement' => $userAchievement,
                    ];
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $unlockedAchievements;
    }

    /**
     * Determinar si un logro debe ser desbloqueado
     */
    private function shouldUnlockAchievement(User $user, Achievement $achievement, array $context): bool
    {
        $requirements = $achievement->requirements;
        $type = $requirements['type'] ?? 'count';
        $target = $requirements['target'] ?? 1;

        switch ($type) {
            case 'games_played':
                return $user->games_played_count >= $target;

            case 'games_won':
                return $user->games_won_count >= $target;

            case 'total_winnings':
                return $user->total_winnings >= $target;

            case 'login_streak':
                // Pendiente: no hay lógica de racha de login implementada todavía
                // (ver Fase 5 del plan en progreso/xp-logros-plan.md).
                return false;

            case 'level_reached':
                return $user->getCurrentLevel() >= $target;

            case 'referrals':
                return $user->referrals()->count() >= $target;

            case 'first_game':
                return $user->games_played_count >= 1;

            case 'first_win':
                return $user->games_won_count >= 1;

            case 'custom':
                return $this->checkCustomRequirement($user, $achievement, $context);

            default:
                return false;
        }
    }

    /**
     * Verificar requisitos personalizados. Ningún logro activo usa este tipo hoy
     * (los que lo requerían quedaron desactivados en el seeder hasta implementarse).
     */
    private function checkCustomRequirement(User $user, Achievement $achievement, array $context): bool
    {
        return false;
    }

    /**
     * Obtener logros por categoría
     */
    public function getAchievementsByCategory(User $user, string $category): array
    {
        $achievements = Achievement::active()
            ->byCategory($category)
            ->orderByOrder()
            ->get();

        return $achievements->map(function ($achievement) use ($user) {
            $progress = $achievement->getProgressForUser($user);
            
            return [
                'id' => $achievement->id,
                'code' => $achievement->code,
                'name' => $achievement->name,
                'description' => $achievement->description,
                'icon' => $achievement->icon_url,
                'badge_color' => $achievement->badge_color,
                'badge_style' => $achievement->badge_style,
                'xp_reward' => $achievement->xp_reward,
                'requirements' => $achievement->requirements,
                'progress' => $progress,
                'is_unlocked' => $progress['is_unlocked'],
                'unlocked_at' => $progress['unlocked_at'],
            ];
        })->toArray();
    }

    /**
     * Desbloquear un logro específico manualmente
     */
    public function unlockAchievement(User $user, string $achievementCode): ?UserAchievement
    {
        $achievement = Achievement::where('code', $achievementCode)->first();
        
        if (!$achievement) {
            return null;
        }

        if ($user->hasAchievement($achievementCode)) {
            return null;
        }

        return $achievement->unlockForUser($user);
    }
}
