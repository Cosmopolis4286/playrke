<?php

namespace App\Domains\Games\Bingo\Repositories\Pattern;

use App\Domains\Games\Bingo\Contracts\Pattern\BingoPatternRepositoryInterface;
use App\Domains\Games\Bingo\Models\Pattern\BingoPattern;
use App\Repositories\BaseRepository;
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
     * Crear nuevo patrón
     */
    public function createPattern(array $patternData) : BingoPattern
    {
        return $this->create($patternData);
    }

    /**
     * Verificar si existe un patrón por nombre y usuario
     */
    public function existsByNameAndUser(string $name, string $userId) : bool
    {
        return $this->model->where('name', $name)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getByUserAndDefault(string $userId, array $fields = ['*'], ?string $status = null) : Collection
    {
        $query = $this->model
            ->where(function($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhere('default_pattern', 'Y');
            });

        if (! empty($fields)) {
            $query->select($fields);
        }

        if (! empty($status)) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    /**
     * Actualizar patrón
     */
    public function updatePattern(string $patternId, array $updateData) : bool
    {
        return $this->model->where('id', $patternId)
            ->update($updateData) > 0;
    }

    /**
     * Actualizar estado de patrón
     */
    public function updatePatternStatus(string $patternId, string $newStatus) : bool
    {
        return $this->updatePattern($patternId, ['status' => $newStatus]);
    }
}
