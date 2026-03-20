<?php

namespace App\Services\User;

use App\Models\User;
use App\Models\UserLevel;
use Illuminate\Support\Facades\DB;

class UserLevelService
{
    public function createLevelForUser(User $user): UserLevel
    {
        if ($user->userLevel) {
            return $user->userLevel;
        }

        return UserLevel::createForUser($user);
    }

    public function addXpToUser(User $user, int $amount, string $reason = null): array
    {
        if ($amount <= 0) {
            return [
                'success' => false,
                'message' => 'XP amount must be positive',
                'leveled_up' => false,
            ];
        }

        DB::beginTransaction();
        try {
            $userLevel = $user->getOrCreateLevel();
            $previousLevel = $userLevel->current_level;
            $leveledUp = $userLevel->addXp($amount);

            $result = [
                'success' => true,
                'message' => 'XP added successfully',
                'leveled_up' => $leveledUp,
                'previous_level' => $previousLevel,
                'new_level' => $userLevel->current_level,
                'xp_added' => $amount,
                'current_xp' => $userLevel->current_xp,
                'total_xp' => $userLevel->total_xp,
                'xp_to_next_level' => $userLevel->xp_to_next_level,
                'progress_percentage' => $userLevel->progress_percentage,
            ];

            if ($reason) {
                $this->logXpTransaction($user, $amount, $reason, $leveledUp);
            }

            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to add XP: ' . $e->getMessage(),
                'leveled_up' => false,
            ];
        }
    }

    public function removeXpFromUser(User $user, int $amount, string $reason = null): array
    {
        if ($amount <= 0) {
            return [
                'success' => false,
                'message' => 'XP amount must be positive',
            ];
        }

        $userLevel = $user->getOrCreateLevel();

        if ($userLevel->current_xp < $amount) {
            return [
                'success' => false,
                'message' => 'Insufficient XP',
            ];
        }

        DB::beginTransaction();
        try {
            $userLevel->current_xp -= $amount;
            $userLevel->total_xp = max(0, $userLevel->total_xp - $amount);
            $userLevel->save();

            $result = [
                'success' => true,
                'message' => 'XP removed successfully',
                'xp_removed' => $amount,
                'current_xp' => $userLevel->current_xp,
                'total_xp' => $userLevel->total_xp,
                'progress_percentage' => $userLevel->progress_percentage,
            ];

            if ($reason) {
                $this->logXpTransaction($user, -$amount, $reason, false);
            }

            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to remove XP: ' . $e->getMessage(),
            ];
        }
    }

    public function getUserLevelInfo(User $user): array
    {
        $userLevel = $user->getOrCreateLevel();

        return [
            'level' => $userLevel->current_level,
            'current_xp' => $userLevel->current_xp,
            'total_xp' => $userLevel->total_xp,
            'xp_to_next_level' => $userLevel->xp_to_next_level,
            'xp_needed_for_next_level' => $userLevel->xp_needed_for_next_level,
            'progress_percentage' => $userLevel->progress_percentage,
            'leveled_up_at' => $userLevel->leveled_up_at?->toISOString(),
        ];
    }

    public function getLeaderboard(int $limit = 10, string $sortBy = 'total_xp'): array
    {
        $query = UserLevel::with('user:id,username,first_name,last_name,profile_photo_url');

        switch ($sortBy) {
            case 'level':
                $query->orderBy('current_level', 'desc')
                      ->orderBy('total_xp', 'desc');
                break;
            case 'current_xp':
                $query->orderBy('current_xp', 'desc')
                      ->orderBy('current_level', 'desc');
                break;
            default:
                $query->orderBy('total_xp', 'desc')
                      ->orderBy('current_level', 'desc');
        }

        return $query->limit($limit)->get()->map(function ($userLevel) {
            return [
                'user' => [
                    'id' => $userLevel->user->id,
                    'username' => $userLevel->user->username,
                    'full_name' => $userLevel->user->full_name,
                    'profile_photo_url' => $userLevel->user->profile_photo_url,
                ],
                'level' => $userLevel->current_level,
                'current_xp' => $userLevel->current_xp,
                'total_xp' => $userLevel->total_xp,
                'progress_percentage' => $userLevel->progress_percentage,
                'rank' => null,
            ];
        })->toArray();
    }

    public function calculateXpReward(string $action, array $context = []): int
    {
        $baseRewards = [
            'daily_login' => 10,
            'game_played' => 25,
            'game_won' => 100,
            'referral_signup' => 500,
            'profile_completed' => 50,
            'first_game' => 100,
            'streak_7_days' => 200,
            'streak_30_days' => 1000,
        ];

        $baseReward = $baseRewards[$action] ?? 0;

        if (isset($context['multiplier'])) {
            $baseReward = (int) ($baseReward * $context['multiplier']);
        }

        if (isset($context['bonus'])) {
            $baseReward += $context['bonus'];
        }

        return max(0, $baseReward);
    }

    public function resetUserLevel(User $user): array
    {
        DB::beginTransaction();
        try {
            $userLevel = $user->userLevel;
            
            if ($userLevel) {
                $userLevel->delete();
            }

            $newLevel = $this->createLevelForUser($user);

            DB::commit();
            return [
                'success' => true,
                'message' => 'User level reset successfully',
                'new_level' => $newLevel->current_level,
                'current_xp' => $newLevel->current_xp,
                'total_xp' => $newLevel->total_xp,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to reset user level: ' . $e->getMessage(),
            ];
        }
    }

    private function logXpTransaction(User $user, int $amount, string $reason, bool $leveledUp): void
    {
        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties([
                'xp_amount' => $amount,
                'reason' => $reason,
                'leveled_up' => $leveledUp,
                'new_level' => $user->userLevel->current_level,
                'total_xp' => $user->userLevel->total_xp,
            ])
            ->log('XP transaction: ' . $reason);
    }
}
