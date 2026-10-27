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
 * App\Services\Roles\Player\External\ExternalPlayerLinkService para el flujo
 * de creación real).
 *
 * generateUniqueLink() usa 28 caracteres para coincidir con el generador real
 * de ExternalPlayerLinkService::resolveOrCreatePlayer() y con
 * external_players.link (string(28)) — antes de este fix existían dos
 * generadores inconsistentes (20 vs 28 chars) sin que ninguno de los dos se
 * usara realmente en conjunto.
 */
class ExternalPlayerRepository extends BaseRepository implements ExternalPlayerRepositoryInterface
{
    private const LINK_LENGTH = 28;

    public function __construct(ExternalPlayer $model)
    {
        $this->model = $model;
    }

    public function existsByLink(string $link) : bool
    {
        return $this->model->where('link', $link)->exists();
    }

    public function findByPhoneAndRoom(string $phone, string $countryId, string $gameId, string $roomId, ?array $sellerUserIds = null) : ?ExternalPlayer
    {
        return $this->model->where('phone', $phone)
            ->where('country_id', $countryId)
            ->where('game_id', $gameId)
            ->where('room_id', $roomId)
            ->when($sellerUserIds !== null, fn ($q) => $q->whereIn('seller_user_id', $sellerUserIds))
            ->first();
    }

    public function generateUniqueLink() : string
    {
        do {
            $link = Str::random(self::LINK_LENGTH);
        } while ($this->existsByLink($link));

        return $link;
    }

    public function getActivePlayersByGame(string $gameId) : Collection
    {
        return $this->model->where('game_id', $gameId)->where('status', 'ACTIVE')->get();
    }

    public function getBlockedPlayersByGame(string $gameId) : Collection
    {
        return $this->model->where('game_id', $gameId)->where('status', 'BLOCKED')->get();
    }

    public function getByCountry(string $countryId) : Collection
    {
        return $this->model->where('country_id', $countryId)->get();
    }

    public function getByCreatorUser(string $creatorUserId) : Collection
    {
        return $this->model->where('creator_user_id', $creatorUserId)->get();
    }

    public function getByCurrency(string $currencyId) : Collection
    {
        return $this->model->where('currency_id', $currencyId)->get();
    }

    public function getByGame(string $gameId) : Collection
    {
        return $this->model->where('game_id', $gameId)->get();
    }

    public function getByLink(string $playerLink) : ?ExternalPlayer
    {
        return $this->model->where('link', $playerLink)->first();
    }

    public function getByRoom(string $roomId) : Collection
    {
        return $this->model->where('room_id', $roomId)->get();
    }

    public function getByRoomUser(string $gameId, string $roomUserId) : ?ExternalPlayer
    {
        return $this->model->where('game_id', $gameId)
            ->where('room_user_id', $roomUserId)
            ->first();
    }

    public function getBySeller(string $sellerUserId) : Collection
    {
        return $this->model->where('seller_user_id', $sellerUserId)->get();
    }

    public function getInactivePlayersByGame(string $gameId) : Collection
    {
        return $this->model->where('game_id', $gameId)->where('status', 'INACTIVE')->get();
    }

    public function getPlayersByStatus(string $status = 'ACTIVE') : Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * Estadísticas globales si $gameId es null, o acotadas a un juego.
     *
     * Una sola query con SUM/CASE en vez de 4 COUNT separados (uno por
     * estado + el total) — mismo resultado, una sola vuelta a la base de
     * datos independientemente de cuántos estados existan.
     */
    public function getPlayerStatistics(?string $gameId = null, ?array $sellerUserIds = null) : array
    {
        $query = $this->model->newQuery();

        if ($gameId) {
            $query->where('game_id', $gameId);
        }

        if ($sellerUserIds !== null) {
            $query->whereIn('seller_user_id', $sellerUserIds);
        }

        $row = $query->selectRaw(
            "COUNT(*) as total,
             SUM(CASE WHEN status = 'ACTIVE' THEN 1 ELSE 0 END) as active,
             SUM(CASE WHEN status = 'INACTIVE' THEN 1 ELSE 0 END) as inactive,
             SUM(CASE WHEN status = 'BLOCKED' THEN 1 ELSE 0 END) as blocked",
        )->first();

        return [
            'total' => (int) $row->total,
            'active' => (int) $row->active,
            'inactive' => (int) $row->inactive,
            'blocked' => (int) $row->blocked,
        ];
    }

    public function getPlayersWithPurchaseCode(string $purchaseCodeId) : Collection
    {
        return $this->model->where('purchase_code_id', $purchaseCodeId)->get();
    }

    public function getValueByLink(string $link, string $column) : mixed
    {
        return $this->model->where('link', $link)->value($column);
    }

    public function getValueByRoomUser(string $gameId, string $roomUserId, string $column) : mixed
    {
        return $this->model->where('game_id', $gameId)
            ->where('room_user_id', $roomUserId)
            ->value($column);
    }

    public function regenerateLink(string $playerId, int $expirationDays) : ?ExternalPlayer
    {
        $player = $this->model->find($playerId);

        if (! $player) {
            return null;
        }

        $player->update([
            'link' => $this->generateUniqueLink(),
            'expiration_date' => now()->addDays($expirationDays),
        ]);

        return $player->fresh();
    }

    public function searchByIdentification(string $identification) : Collection
    {
        return $this->model->where('identification', 'LIKE', "%{$identification}%")->get();
    }

    public function searchByName(string $firstName, ?string $lastName = null) : Collection
    {
        $query = $this->model->where('first_name', 'LIKE', "%{$firstName}%");

        if ($lastName) {
            $query->where('last_name', 'LIKE', "%{$lastName}%");
        }

        return $query->get();
    }

    public function searchByPhone(string $phone) : Collection
    {
        return $this->model->where('phone', 'LIKE', "%{$phone}%")->get();
    }

    public function updatePlayerStatus(string $playerId, string $status) : bool
    {
        return $this->model->where('id', $playerId)->update(['status' => $status]) > 0;
    }
}
