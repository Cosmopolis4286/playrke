<?php

namespace App\Repositories\Games\Shared\Utils;

use App\Models\Games\Shared\Utils\GameRule;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Games\Shared\Utils\Contracts\GameRuleRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class GameRuleRepository extends BaseRepository implements GameRuleRepositoryContract
{
    public function __construct(GameRule $model)
    {
        parent::__construct($model);
    }

    public function getByUser($userId): Collection
    {
        return $this->model->where('user_id', $userId)->get();
    }

    public function getByGame($gameId): Collection
    {
        return $this->model->where('game_id', $gameId)->get();
    }

    public function getByUserAndGame($userId, $gameId): ?GameRule
    {
        return $this->model->where('user_id', $userId)
            ->where('game_id', $gameId)
            ->first();
    }

    public function getActiveRules(): Collection
    {
        return $this->model->where('is_active', true)->get();
    }

    public function getInactiveRules(): Collection
    {
        return $this->model->where('is_active', false)->get();
    }

    public function getRulesByStatus(bool $isActive): Collection
    {
        return $this->model->where('is_active', $isActive)->get();
    }

    public function getByEncrypted(string $encrypted): ?GameRule
    {
        return $this->model->where('encrypted', $encrypted)->first();
    }

    public function existsEncrypted(string $encrypted): bool
    {
        return $this->model->where('encrypted', $encrypted)->exists();
    }

    public function getRulesByGameAndStatus($gameId, bool $isActive = true): Collection
    {
        return $this->model->where('game_id', $gameId)
            ->where('is_active', $isActive)
            ->get();
    }

    public function getUserRulesByStatus($userId, bool $isActive = true): Collection
    {
        return $this->model->where('user_id', $userId)
            ->where('is_active', $isActive)
            ->get();
    }

    public function createRule($userId, $gameId, string $rules, string $encrypted, ?string $image = null, bool $isActive = true): GameRule
    {
        return $this->model->create([
            'user_id' => $userId,
            'game_id' => $gameId,
            'rules' => $rules,
            'encrypted' => $encrypted,
            'image' => $image,
            'is_active' => $isActive
        ]);
    }

    public function updateRuleStatus($ruleId, bool $isActive): bool
    {
        return $this->model->where('id', $ruleId)
            ->update(['is_active' => $isActive]) > 0;
    }

    public function activateRule($ruleId): bool
    {
        return $this->updateRuleStatus($ruleId, true);
    }

    public function deactivateRule($ruleId): bool
    {
        return $this->updateRuleStatus($ruleId, false);
    }

    public function updateRuleContent($ruleId, string $rules): bool
    {
        return $this->model->where('id', $ruleId)
            ->update(['rules' => $rules]) > 0;
    }

    public function updateRuleImage($ruleId, ?string $image): bool
    {
        return $this->model->where('id', $ruleId)
            ->update(['image' => $image]) > 0;
    }

    public function deleteRule($ruleId): bool
    {
        return $this->model->where('id', $ruleId)->delete() > 0;
    }

    public function bulkUpdateStatus(array $ruleIds, bool $isActive): int
    {
        return $this->model->whereIn('id', $ruleIds)
            ->update(['is_active' => $isActive]);
    }

    public function getRuleStatistics(): array
    {
        $total = $this->model->count();
        $active = $this->model->where('is_active', true)->count();
        $inactive = $this->model->where('is_active', false)->count();
        $withImages = $this->model->whereNotNull('image')->count();

        return [
            'total_rules' => $total,
            'active_rules' => $active,
            'inactive_rules' => $inactive,
            'rules_with_images' => $withImages,
            'active_percentage' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
            'image_percentage' => $total > 0 ? round(($withImages / $total) * 100, 2) : 0
        ];
    }

    public function getGameStatistics($gameId): array
    {
        $total = $this->model->where('game_id', $gameId)->count();
        $active = $this->model->where('game_id', $gameId)
            ->where('is_active', true)->count();
        $users = $this->model->where('game_id', $gameId)
            ->distinct('user_id')->count();
        $withImages = $this->model->where('game_id', $gameId)
            ->whereNotNull('image')->count();

        return [
            'total_rules' => $total,
            'active_rules' => $active,
            'total_users' => $users,
            'rules_with_images' => $withImages,
            'average_rules_per_user' => $users > 0 ? round($total / $users, 2) : 0
        ];
    }

    public function getUserStatistics($userId): array
    {
        $total = $this->model->where('user_id', $userId)->count();
        $active = $this->model->where('user_id', $userId)
            ->where('is_active', true)->count();
        $games = $this->model->where('user_id', $userId)
            ->distinct('game_id')->count();
        $withImages = $this->model->where('user_id', $userId)
            ->whereNotNull('image')->count();

        return [
            'total_rules' => $total,
            'active_rules' => $active,
            'total_games' => $games,
            'rules_with_images' => $withImages,
            'average_rules_per_game' => $games > 0 ? round($total / $games, 2) : 0
        ];
    }

    public function generateUniqueEncrypted(): string
    {
        do {
            $encrypted = Str::random(12);
        } while ($this->existsEncrypted($encrypted));

        return $encrypted;
    }

    public function searchRulesByContent(string $searchTerm): Collection
    {
        return $this->model->where('rules', 'LIKE', "%{$searchTerm}%")
            ->where('is_active', true)
            ->get();
    }

    public function getRecentRules(int $days = 30): Collection
    {
        return $this->model->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getRulesByUserWithGames($userId): Collection
    {
        return $this->model->where('user_id', $userId)
            ->with('game')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}