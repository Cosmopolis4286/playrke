<?php

namespace App\Repositories\Users\Roles\Commerce;

use App\Models\Users\Commerce\CommerceGame;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Users\Roles\Commerce\Contracts\CommerceGameRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository para manejar operaciones de permisos de juegos de comercios
 */
class CommerceGameRepository extends BaseRepository implements CommerceGameRepositoryContract
{
    /**
     * Constructor del repository
     *
     * @param CommerceGame $model
     */
    public function __construct(CommerceGame $model)
    {
        parent::__construct($model);
    }

    /**
     * Obtiene permisos por comercio
     *
     * @param mixed $commerceId ID del comercio
     * @return Collection
     */
    public function getByCommerce($commerceId): Collection
    {
        return $this->model->where('commerce_id', $commerceId)->get();
    }

    /**
     * Obtiene permisos por juego
     *
     * @param mixed $gameId ID del juego
     * @return Collection
     */
    public function getByGame($gameId): Collection
    {
        return $this->model->where('game_id', $gameId)->get();
    }

    /**
     * Obtiene juegos disponibles/permitidos para un comercio
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed|null $exceptGameId ID de juego a excluir
     * @return array
     */
    public function getAllowedGames($commerceId, $exceptGameId = null): array
    {
        $query = $this->model->select('game_id')
            ->where('commerce_id', $commerceId)
            ->where('status', 'ACTIVE');

        if ($exceptGameId !== null) {
            $query->where('game_id', '<>', $exceptGameId);
        }

        return $query->distinct()->pluck('game_id')->toArray();
    }

    /**
     * Obtiene permisos activos por comercio
     *
     * @param mixed $commerceId ID del comercio
     * @return Collection
     */
    public function getActiveByCommerce($commerceId): Collection
    {
        return $this->model->where('commerce_id', $commerceId)
            ->where('status', true)
            ->get();
    }

    /**
     * Obtiene permisos inactivos por comercio
     *
     * @param mixed $commerceId ID del comercio
     * @return Collection
     */
    public function getInactiveByCommerce($commerceId): Collection
    {
        return $this->model->where('commerce_id', $commerceId)
            ->where('status', false)
            ->get();
    }

    /**
     * Obtiene comercios con permiso para un juego
     *
     * @param mixed $gameId ID del juego
     * @return Collection
     */
    public function getCommercesWithGamePermission($gameId): Collection
    {
        return $this->model->where('game_id', $gameId)
            ->where('status', true)
            ->get();
    }

    /**
     * Verifica si un comercio tiene permiso para un juego
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $gameId ID del juego
     * @return bool
     */
    public function hasPermission($commerceId, $gameId): bool
    {
        return $this->model->where('commerce_id', $commerceId)
            ->where('game_id', $gameId)
            ->where('status', true)
            ->exists();
    }

    /**
     * Obtiene permiso específico por comercio y juego
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $gameId ID del juego
     * @return CommerceGame|null
     */
    public function getPermission($commerceId, $gameId): ?CommerceGame
    {
        return $this->model->where('commerce_id', $commerceId)
            ->where('game_id', $gameId)
            ->first();
    }

    /**
     * Activa permiso para un comercio y juego
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $gameId ID del juego
     * @return bool
     */
    public function activatePermission($commerceId, $gameId): bool
    {
        return $this->model->where('commerce_id', $commerceId)
            ->where('game_id', $gameId)
            ->update(['status' => true]) > 0;
    }

    /**
     * Desactiva permiso para un comercio y juego
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $gameId ID del juego
     * @return bool
     */
    public function deactivatePermission($commerceId, $gameId): bool
    {
        return $this->model->where('commerce_id', $commerceId)
            ->where('game_id', $gameId)
            ->update(['status' => false]) > 0;
    }

    /**
     * Activa todos los permisos de un comercio
     *
     * @param mixed $commerceId ID del comercio
     * @return int Número de permisos activados
     */
    public function activateAllByCommerce($commerceId): int
    {
        return $this->model->where('commerce_id', $commerceId)
            ->update(['status' => true]);
    }

    /**
     * Desactiva todos los permisos de un comercio
     *
     * @param mixed $commerceId ID del comercio
     * @return int Número de permisos desactivados
     */
    public function deactivateAllByCommerce($commerceId): int
    {
        return $this->model->where('commerce_id', $commerceId)
            ->update(['status' => false]);
    }

    /**
     * Crea o actualiza permiso
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $gameId ID del juego
     * @param bool $isActive Estado del permiso
     * @return CommerceGame
     */
    public function createOrUpdatePermission($commerceId, $gameId, bool $isActive = true): CommerceGame
    {
        return $this->model->updateOrCreate(
            [
                'commerce_id' => $commerceId,
                'game_id' => $gameId
            ],
            [
                'status' => $isActive
            ]
        );
    }

    /**
     * Obtiene todos los permisos activos
     *
     * @return Collection
     */
    public function getAllActivePermissions(): Collection
    {
        return $this->model->where('status', true)->get();
    }

    /**
     * Obtiene todos los permisos inactivos
     *
     * @return Collection
     */
    public function getAllInactivePermissions(): Collection
    {
        return $this->model->where('status', false)->get();
    }
}
