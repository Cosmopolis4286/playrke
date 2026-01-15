<?php

namespace App\Repositories\Games\Shared\Utils\Contracts;

use App\Repositories\Base\Contracts\BaseRepositoryContract;
use App\Models\Shared\Utils\GameRule;
use Illuminate\Database\Eloquent\Collection;

interface GameRuleRepositoryContract extends BaseRepositoryContract
{
    public function getByUser($userId): Collection;

    public function getByGame($gameId): Collection;

    public function getByUserAndGame($userId, $gameId): ?GameRule;

    public function getActiveRules(): Collection;

    public function getInactiveRules(): Collection;

    public function getRulesByStatus(bool $isActive): Collection;

    public function getByEncrypted(string $encrypted): ?GameRule;

    public function existsEncrypted(string $encrypted): bool;

    public function getRulesByGameAndStatus($gameId, bool $isActive = true): Collection;

    public function getUserRulesByStatus($userId, bool $isActive = true): Collection;

    public function createRule($userId, $gameId, string $rules, string $encrypted, ?string $image = null, bool $isActive = true): GameRule;

    public function updateRuleStatus($ruleId, bool $isActive): bool;

    public function activateRule($ruleId): bool;

    public function deactivateRule($ruleId): bool;

    public function updateRuleContent($ruleId, string $rules): bool;

    public function updateRuleImage($ruleId, ?string $image): bool;

    public function deleteRule($ruleId): bool;

    public function bulkUpdateStatus(array $ruleIds, bool $isActive): int;

    public function getRuleStatistics(): array;

    public function getGameStatistics($gameId): array;

    public function getUserStatistics($userId): array;

    public function generateUniqueEncrypted(): string;

    public function searchRulesByContent(string $searchTerm): Collection;

    public function getRecentRules(int $days = 30): Collection;

    public function getRulesByUserWithGames($userId): Collection;
}