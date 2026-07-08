<?php

namespace App\Domains\Games\Bingo\Contracts\Pattern;

use App\Contracts\BaseRepositoryInterface;
use App\Domains\Games\Bingo\Models\Pattern\BingoPattern;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de patrones de bingo
 */
interface BingoPatternRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Obtener patrones por usuario y los patrones por defecto
     *
     * @param string $userId
     * @param array $fields
     * @param string|null $status
     * @return Collection<int, BingoPattern>
     */
    public function getByUserAndDefault(string $userId, array $fields = ['*'], ?string $status = null): Collection;

    /**
     * Verificar si existe un patrón por nombre y usuario
     *
     * @param string $name
     * @param string $userId
     * @return bool
     */
    public function existsByNameAndUser(string $name, string $userId): bool;

    /**
     * Crear nuevo patrón
     *
     * @param array $patternData
     * @return BingoPattern
     */
    public function createPattern(array $patternData): BingoPattern;

    /**
     * Actualizar patrón
     *
     * @param string $patternId
     * @param array $updateData
     * @return bool
     */
    public function updatePattern(string $patternId, array $updateData): bool;

    /**
     * Actualizar estado de patrón
     *
     * @param string $patternId
     * @param string $newStatus
     * @return bool
     */
    public function updatePatternStatus(string $patternId, string $newStatus): bool;
}
