<?php

namespace App\Repositories\Roles\Player\External;

use App\Contracts\Roles\Player\External\ExternalPlayerRepositoryInterface;
use App\Models\Roles\Player\External\ExternalPlayer;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Repositorio de ExternalPlayer (jugadores de Bingo captados por un vendedor
 * sin cuenta en la plataforma, identificados por `link` temporal — ver
 * App\Traits\Users\PlayerLinkTrait para el flujo de creación real).
 *
 * generateUniqueLink() usa 28 caracteres para coincidir con el generador real
 * de PlayerLinkTrait::createExternalPlayers() y con external_players.link
 * (string(28)) — antes de este fix existían dos generadores inconsistentes
 * (20 vs 28 chars) sin que ninguno de los dos se usara realmente en conjunto.
 */
class ExternalPlayerRepository extends BaseRepository implements ExternalPlayerRepositoryInterface
{
    private const LINK_LENGTH = 28;

    public function __construct(ExternalPlayer $model)
    {
        $this->model = $model;
    }

    public function getByLink(string $playerLink): ?ExternalPlayer
    {
        return $this->model->where('link', $playerLink)->first();
    }

    public function getValueByLink(string $link, string $column): mixed
    {
        return $this->model->where('link', $link)->value($column);
    }

    public function getByRoomUser(string $gameId, string $roomUserId): ?ExternalPlayer
    {
        return $this->model->where('game_id', $gameId)
            ->where('room_user_id', $roomUserId)
            ->first();
    }

    public function getValueByRoomUser(string $gameId, string $roomUserId, string $column): mixed
    {
        return $this->model->where('game_id', $gameId)
            ->where('room_user_id', $roomUserId)
            ->value($column);
    }

    public function getByGame(string $gameId): Collection
    {
        return $this->model->where('game_id', $gameId)->get();
    }

    public function getByRoom(string $roomId): Collection
    {
        return $this->model->where('room_id', $roomId)->get();
    }

    public function getByCreatorUser(string $creatorUserId): Collection
    {
        return $this->model->where('creator_user_id', $creatorUserId)->get();
    }

    public function getBySeller(string $sellerUserId): Collection
    {
        return $this->model->where('seller_user_id', $sellerUserId)->get();
    }

    public function getByCountry(string $countryId): Collection
    {
        return $this->model->where('country_id', $countryId)->get();
    }

    public function getByCurrency(string $currencyId): Collection
    {
        return $this->model->where('currency_id', $currencyId)->get();
    }

    public function getActivePlayersByGame(string $gameId): Collection
    {
        return $this->model->where('game_id', $gameId)->where('status', 'ACTIVE')->get();
    }

    public function getInactivePlayersByGame(string $gameId): Collection
    {
        return $this->model->where('game_id', $gameId)->where('status', 'INACTIVE')->get();
    }

    public function getBlockedPlayersByGame(string $gameId): Collection
    {
        return $this->model->where('game_id', $gameId)->where('status', 'BLOCKED')->get();
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

    public function updatePlayerStatus(string $playerId, string $status): bool
    {
        return $this->model->where('id', $playerId)->update(['status' => $status]) > 0;
    }

    public function generateUniqueLink(): string
    {
        do {
            $link = Str::random(self::LINK_LENGTH);
        } while ($this->existsByLink($link));

        return $link;
    }

    public function existsByLink(string $link): bool
    {
        return $this->model->where('link', $link)->exists();
    }

    public function getPlayersWithPurchaseCode(string $purchaseCodeId): Collection
    {
        return $this->model->where('purchase_code_id', $purchaseCodeId)->get();
    }

    /**
     * Estadísticas globales si $gameId es null, o acotadas a un juego.
     */
    public function getPlayerStatistics(?string $gameId = null): array
    {
        $query = fn () => $gameId ? $this->model->where('game_id', $gameId) : $this->model->newQuery();

        return [
            'total'    => $query()->count(),
            'active'   => $query()->where('status', 'ACTIVE')->count(),
            'inactive' => $query()->where('status', 'INACTIVE')->count(),
            'blocked'  => $query()->where('status', 'BLOCKED')->count(),
        ];
    }
}
