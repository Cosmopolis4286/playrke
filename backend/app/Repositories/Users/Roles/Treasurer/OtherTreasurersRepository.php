<?php

namespace App\Repositories\Users\Roles\Treasurer;

use App\Models\Users\Treasurer\OtherTreasurers;
use App\Repositories\BaseRepository;
use App\Repositories\Users\Roles\Treasurer\Contracts\OtherTreasurersRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositorio para la gestión de otros tesoreros.
 *
 * Implementa las operaciones específicas para el modelo OtherTreasurers,
 * incluyendo gestión de permisos de juegos, monedas y estados.
 */
class OtherTreasurersRepository extends BaseRepository implements OtherTreasurersRepositoryContract
{
    /**
     * Constructor del repositorio.
     *
     * @param OtherTreasurers $model Instancia del modelo OtherTreasurers
     */
    public function __construct(OtherTreasurers $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function getByCreatorUser(string $creatorUserId): ?OtherTreasurers
    {
        return $this->model->where('creator_user_id', $creatorUserId)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function existsByCreatorUser(string $creatorUserId): bool
    {
        return $this->model->where('creator_user_id', $creatorUserId)->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveTesoreros(): Collection
    {
        return $this->getByStatus('ACTIVE');
    }

    /**
     * {@inheritDoc}
     */
    public function getInactiveTesoreros(): Collection
    {
        return $this->getByStatus('INACTIVE');
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockedTesoreros(): Collection
    {
        return $this->getByStatus('BLOCKED');
    }

    /**
     * {@inheritDoc}
     */
    public function getByAllowedGame(string $gameId): Collection
    {
        return $this->model->whereJsonContains('allowed_games', $gameId)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByAllowedCurrency(string $currencyId): Collection
    {
        return $this->model->whereJsonContains('allowed_currencies', $currencyId)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function createTesorero(
        string $creatorUserId,
        array $allowedGames,
        array $allowedCurrencies,
        string $status = 'ACTIVE'
    ): OtherTreasurers {
        return $this->model->create([
            'creator_user_id' => $creatorUserId,
            'allowed_games' => $allowedGames,
            'allowed_currencies' => $allowedCurrencies,
            'status' => $status
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function updateStatus(string $treasurerId, string $status): bool
    {
        return $this->model->where('id', $treasurerId)
            ->update(['status' => $status]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function activateTesorero(string $treasurerId): bool
    {
        return $this->updateStatus($treasurerId, 'ACTIVE');
    }

    /**
     * {@inheritDoc}
     */
    public function deactivateTesorero(string $treasurerId): bool
    {
        return $this->updateStatus($treasurerId, 'INACTIVE');
    }

    /**
     * {@inheritDoc}
     */
    public function blockTesorero(string $treasurerId): bool
    {
        return $this->updateStatus($treasurerId, 'BLOCKED');
    }

    /**
     * {@inheritDoc}
     */
    public function updateAllowedGames(string $treasurerId, array $allowedGames): bool
    {
        return $this->model->where('id', $treasurerId)
            ->update(['allowed_games' => $allowedGames]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function updateAllowedCurrencies(string $treasurerId, array $allowedCurrencies): bool
    {
        return $this->model->where('id', $treasurerId)
            ->update(['allowed_currencies' => $allowedCurrencies]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function addAllowedGame(string $treasurerId, string $gameId): bool
    {
        $tesorero = $this->model->find($treasurerId);

        if (!$tesorero) {
            return false;
        }

        $allowedGames = $tesorero->allowed_games ?? [];

        if (!in_array($gameId, $allowedGames)) {
            $allowedGames[] = $gameId;
            return $this->updateAllowedGames($treasurerId, $allowedGames);
        }

        return true; // Ya existe, consideramos como éxito
    }

    /**
     * {@inheritDoc}
     */
    public function removeAllowedGame(string $treasurerId, string $gameId): bool
    {
        $tesorero = $this->model->find($treasurerId);

        if (!$tesorero) {
            return false;
        }

        $allowedGames = $tesorero->allowed_games ?? [];
        $updatedGames = array_filter($allowedGames, fn($id) => $id !== $gameId);

        return $this->updateAllowedGames($treasurerId, array_values($updatedGames));
    }

    /**
     * {@inheritDoc}
     */
    public function addAllowedCurrency(string $treasurerId, string $currencyId): bool
    {
        $tesorero = $this->model->find($treasurerId);

        if (!$tesorero) {
            return false;
        }

        $allowedCurrencies = $tesorero->allowed_currencies ?? [];

        if (!in_array($currencyId, $allowedCurrencies)) {
            $allowedCurrencies[] = $currencyId;
            return $this->updateAllowedCurrencies($treasurerId, $allowedCurrencies);
        }

        return true; // Ya existe, consideramos como éxito
    }

    /**
     * {@inheritDoc}
     */
    public function removeAllowedCurrency(string $treasurerId, string $currencyId): bool
    {
        $tesorero = $this->model->find($treasurerId);

        if (!$tesorero) {
            return false;
        }

        $allowedCurrencies = $tesorero->allowed_currencies ?? [];
        $updatedCurrencies = array_filter($allowedCurrencies, fn($id) => $id !== $currencyId);

        return $this->updateAllowedCurrencies($treasurerId, array_values($updatedCurrencies));
    }

    /**
     * {@inheritDoc}
     */
    public function deleteTesorero(string $treasurerId): bool
    {
        return $this->model->where('id', $treasurerId)->delete() > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function bulkUpdateStatus(array $treasurerIds, string $status): int
    {
        return $this->model->whereIn('id', $treasurerIds)
            ->update(['status' => $status]);
    }

    /**
     * {@inheritDoc}
     */
    public function getTesorerosStatistics(): array
    {
        $total = $this->model->count();
        $active = $this->model->where('status', 'ACTIVE')->count();
        $inactive = $this->model->where('status', 'INACTIVE')->count();
        $blocked = $this->model->where('status', 'BLOCKED')->count();

        return [
            'total_tesoreros' => $total,
            'active_tesoreros' => $active,
            'inactive_tesoreros' => $inactive,
            'blocked_tesoreros' => $blocked,
            'active_percentage' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
            'inactive_percentage' => $total > 0 ? round(($inactive / $total) * 100, 2) : 0,
            'blocked_percentage' => $total > 0 ? round(($blocked / $total) * 100, 2) : 0
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getPermissionsStatistics(string $treasurerId): array
    {
        $tesorero = $this->model->find($treasurerId);

        if (!$tesorero) {
            return [];
        }

        $allowedGames = $tesorero->allowed_games ?? [];
        $allowedCurrencies = $tesorero->allowed_currencies ?? [];

        return [
            'total_allowed_games' => count($allowedGames),
            'total_allowed_currencies' => count($allowedCurrencies),
            'allowed_games' => $allowedGames,
            'allowed_currencies' => $allowedCurrencies,
            'has_game_permissions' => count($allowedGames) > 0,
            'has_currency_permissions' => count($allowedCurrencies) > 0
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getAllCreatorUsers(): Collection
    {
        return $this->model->distinct()->pluck('creator_user_id');
    }

    /**
     * {@inheritDoc}
     */
    public function getRecentTesoreros(int $days = 30): Collection
    {
        return $this->model->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function hasGameAccess(string $treasurerId, string $gameId): bool
    {
        $tesorero = $this->model->find($treasurerId);

        if (!$tesorero || $tesorero->status !== 'ACTIVE') {
            return false;
        }

        $allowedGames = $tesorero->allowed_games ?? [];
        return in_array($gameId, $allowedGames);
    }

    /**
     * {@inheritDoc}
     */
    public function hasCurrencyAccess(string $treasurerId, string $currencyId): bool
    {
        $tesorero = $this->model->find($treasurerId);

        if (!$tesorero || $tesorero->status !== 'ACTIVE') {
            return false;
        }

        $allowedCurrencies = $tesorero->allowed_currencies ?? [];
        return in_array($currencyId, $allowedCurrencies);
    }
}
