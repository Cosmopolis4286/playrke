<?php

namespace App\Repositories\Users\Roles\Player\External;

use App\Models\Users\Roles\Player\External\ExternalPlayer;
use App\Repositories\BaseRepository;
use App\Repositories\Users\Roles\Player\External\Contracts\ExternalPlayerRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ExternalPlayerRepository extends BaseRepository implements ExternalPlayerRepositoryContract
{
    public function __construct(ExternalPlayer $model)
    {
        parent::__construct($model);
    }

    public function getByLink(string $playerLink): ?ExternalPlayer
    {
        return $this->model->where('link', $playerLink)->first();
    }

    public function getValueByLink(string $link, string $column)
    {
        return $this->model->where('link', $link)->value($column);
    }

    public function getByRoomUser($gameId, $roomUser): ?ExternalPlayer
    {
        return $this->model->where('game_id', $gameId)
            ->where('room_user', $roomUser)
            ->first();
    }

    public function getValueByRoomUser($gameId, $roomUser, string $column)
    {
        return $this->model->where('game_id', $gameId)
            ->where('room_user', $roomUser)
            ->value($column);
    }

    public function getByGame($gameId): Collection
    {
        return $this->model->where('game_id', $gameId)->get();
    }

    public function getByRoom($roomId): Collection
    {
        return $this->model->where('room', $roomId)->get();
    }

    public function getByCreatorUser($creatorUserId): Collection
    {
        return $this->model->where('creator_user_id', $creatorUserId)->get();
    }

    public function getBySeller($sellerId): Collection
    {
        return $this->model->where('seller_id', $sellerId)->get();
    }

    public function getByCountry($countryId): Collection
    {
        return $this->model->where('country_id', $countryId)->get();
    }

    public function getByCurrency($currencyId): Collection
    {
        return $this->model->where('currency_id', $currencyId)->get();
    }

    public function getActivePlayersByGame($gameId): Collection
    {
        return $this->model->where('game_id', $gameId)
            ->where('status', 'ACTIVE')
            ->get();
    }

    public function getInactivePlayersByGame($gameId): Collection
    {
        return $this->model->where('game_id', $gameId)
            ->where('status', 'INACTIVE')
            ->get();
    }

    public function getBlockedPlayersByGame($gameId): Collection
    {
        return $this->model->where('game_id', $gameId)
            ->where('status', 'BLOCKED')
            ->get();
    }

    public function searchByIdentification(string $identification): Collection
    {
        return $this->model->where('identification', 'LIKE', "%{$identification}%")->get();
    }

    public function searchByPhone(string $phone): Collection
    {
        return $this->model->where('phone', 'LIKE', "%{$phone}%")->get();
    }

    public function searchByName(string $firstName, ?string $lastName = null): Collection
    {
        $query = $this->model->where('first_name', 'LIKE', "%{$firstName}%");

        if ($lastName) {
            $query->where('last_name', 'LIKE', "%{$lastName}%");
        }

        return $query->get();
    }

    public function getPlayersByStatus(string $status = 'ACTIVE'): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    public function updatePlayerStatus($playerId, string $status): bool
    {
        return $this->model->where('id', $playerId)
            ->update(['status' => $status]) > 0;
    }

    public function createExternalPlayer(array $data): ExternalPlayer
    {
        if (!isset($data['link'])) {
            $data['link'] = $this->generateUniqueLink();
        }

        return $this->model->create($data);
    }

    public function generateUniqueLink(): string
    {
        do {
            $link = Str::random(20);
        } while ($this->existsByLink($link));

        return $link;
    }

    public function existsByLink(string $link): bool
    {
        return $this->model->where('link', $link)->exists();
    }

    public function getPlayersWithPurchaseCode($purchaseCodeId): Collection
    {
        return $this->model->where('purchase_code_id', $purchaseCodeId)->get();
    }

    public function getPlayerStatistics($gameId): array
    {
        $total = $this->model->where('game_id', $gameId)->count();
        $active = $this->model->where('game_id', $gameId)
            ->where('status', 'ACTIVE')->count();
        $inactive = $this->model->where('game_id', $gameId)
            ->where('status', 'INACTIVE')->count();
        $blocked = $this->model->where('game_id', $gameId)
            ->where('status', 'BLOCKED')->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'blocked' => $blocked
        ];
    }
}