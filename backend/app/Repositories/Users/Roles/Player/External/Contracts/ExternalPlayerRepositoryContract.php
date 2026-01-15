<?php

namespace App\Repositories\Users\Roles\Player\External\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Users\Player\External\ExternalPlayer;
use Illuminate\Database\Eloquent\Collection;

interface ExternalPlayerRepositoryContract extends BaseRepositoryInterface
{
    public function getByLink(string $playerLink): ?ExternalPlayer;

    public function getValueByLink(string $link, string $column);

    public function getByRoomUser($gameId, $roomUser): ?ExternalPlayer;

    public function getValueByRoomUser($gameId, $roomUser, string $column);

    public function getByGame($gameId): Collection;

    public function getByRoom($roomId): Collection;

    public function getByCreatorUser($creatorUserId): Collection;

    public function getBySeller($sellerId): Collection;

    public function getByCountry($countryId): Collection;

    public function getByCurrency($currencyId): Collection;

    public function getActivePlayersByGame($gameId): Collection;

    public function getInactivePlayersByGame($gameId): Collection;

    public function getBlockedPlayersByGame($gameId): Collection;

    public function searchByIdentification(string $identification): Collection;

    public function searchByPhone(string $phone): Collection;

    public function searchByName(string $firstName, ?string $lastName = null): Collection;

    public function getPlayersByStatus(string $status = 'ACTIVE'): Collection;

    public function updatePlayerStatus($playerId, string $status): bool;

    public function createExternalPlayer(array $data): ExternalPlayer;

    public function generateUniqueLink(): string;

    public function existsByLink(string $link): bool;

    public function getPlayersWithPurchaseCode($purchaseCodeId): Collection;

    public function getPlayerStatistics($gameId): array;
}