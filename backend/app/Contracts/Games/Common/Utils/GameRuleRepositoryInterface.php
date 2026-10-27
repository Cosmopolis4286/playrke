<?php

namespace App\Contracts\Games\Common\Utils;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Games\Common\Utils\GameRule;
use Illuminate\Database\Eloquent\Collection;

interface GameRuleRepositoryInterface extends BaseRepositoryInterface
{
    public function activateRule($ruleId) : bool;

    public function bulkUpdateStatus(array $ruleIds, bool $isActive) : int;

    public function createRule($userId, $gameId, string $rules, string $encrypted, ?string $image = null, bool $isActive = true) : GameRule;

    public function deactivateRule($ruleId) : bool;

    public function deleteRule($ruleId) : bool;

    public function existsEncrypted(string $encrypted) : bool;

    public function generateUniqueEncrypted() : string;

    public function getActiveRules() : Collection;

    public function getByEncrypted(string $encrypted) : ?GameRule;

    public function getByGame($gameId) : Collection;

    public function getByUser($userId) : Collection;

    public function getByUserAndGame($userId, $gameId) : ?GameRule;

    public function getGameStatistics($gameId) : array;

    public function getInactiveRules() : Collection;

    public function getRecentRules(int $days = 30) : Collection;

    public function getRulesByGameAndStatus($gameId, bool $isActive = true) : Collection;

    public function getRulesByStatus(bool $isActive) : Collection;

    public function getRulesByUserWithGames($userId) : Collection;

    public function getRuleStatistics() : array;

    public function getUserRulesByStatus($userId, bool $isActive = true) : Collection;

    public function getUserStatistics($userId) : array;

    public function searchRulesByContent(string $searchTerm) : Collection;

    public function updateRuleContent($ruleId, string $rules) : bool;

    public function updateRuleImage($ruleId, ?string $image) : bool;

    public function updateRuleStatus($ruleId, bool $isActive) : bool;
}
