<?php

namespace App\Repositories\Games\Bingo\Pattern;

use App\Domains\Games\Bingo\Models\Pattern\BingoPattern;
use App\Repositories\BaseRepository;
use App\Domains\Games\Bingo\Contracts\Pattern\BingoPatternRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositorio para gestionar patrones de bingo
 */
class BingoPatternRepository extends BaseRepository implements BingoPatternRepositoryInterface
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    public function __construct(BingoPattern $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function getByUserAndDefault(string $userId, array $fields = ['*'], ?string $status = null): Collection
    {
        $query = $this->model
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhere('default_pattern', 'Y');
            });

        if (!empty($fields)) {
            $query->select($fields);
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    /**
     * Verificar si existe un patrón por nombre y usuario
     *
     * @param string $name
     * @param string $userId
     * @return bool
     */
    public function existsByNameAndUser(string $name, string $userId): bool
    {
        return $this->model->where('name', $name)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Crear nuevo patrón
     *
     * @param array $patternData
     * @return BingoPattern
     */
    public function createPattern(array $patternData): BingoPattern
    {
        return $this->create($patternData);
    }

    /**
     * Actualizar patrón
     *
     * @param string $patternId
     * @param array $updateData
     * @return bool
     */
    public function updatePattern(string $patternId, array $updateData): bool
    {
        return $this->model->where('id', $patternId)
            ->update($updateData) > 0;
    }

    /**
     * Actualizar estado de patrón
     *
     * @param string $patternId
     * @param string $newStatus
     * @return bool
     */
    public function updatePatternStatus(string $patternId, string $newStatus): bool
    {
        return $this->updatePattern($patternId, ['status' => $newStatus]);
    }
}
