<?php

namespace App\Contracts\Roles\Player\External;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Roles\Player\External\ExternalPlayer;
use Illuminate\Database\Eloquent\Collection;

interface ExternalPlayerRepositoryInterface extends BaseRepositoryInterface
{
    public function getByLink(string $playerLink): ?ExternalPlayer;

    public function getValueByLink(string $link, string $column): mixed;

    public function getByRoomUser(string $gameId, string $roomUserId): ?ExternalPlayer;

    public function getValueByRoomUser(string $gameId, string $roomUserId, string $column): mixed;

    public function getByGame(string $gameId): Collection;

    public function getByRoom(string $roomId): Collection;

    public function getByCreatorUser(string $creatorUserId): Collection;

    public function getBySeller(string $sellerUserId): Collection;

    public function getByCountry(string $countryId): Collection;

    public function getByCurrency(string $currencyId): Collection;

    public function getActivePlayersByGame(string $gameId): Collection;

    public function getInactivePlayersByGame(string $gameId): Collection;

    public function getBlockedPlayersByGame(string $gameId): Collection;

    public function searchByIdentification(string $identification): Collection;

    public function searchByPhone(string $phone): Collection;

    public function searchByName(string $firstName, ?string $lastName = null): Collection;

    public function getPlayersByStatus(string $status = 'ACTIVE'): Collection;

    public function updatePlayerStatus(string $playerId, string $status): bool;

    public function generateUniqueLink(): string;

    public function existsByLink(string $link): bool;

    public function getPlayersWithPurchaseCode(string $purchaseCodeId): Collection;

    public function getPlayerStatistics(?string $gameId = null): array;
}
