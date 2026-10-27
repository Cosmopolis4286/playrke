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
     * Crear nuevo patrón
     */
    public function createPattern(array $patternData) : BingoPattern;

    /**
     * Verificar si existe un patrón por nombre y usuario
     */
    public function existsByNameAndUser(string $name, string $userId) : bool;

    /**
     * Obtener patrones por usuario y los patrones por defecto
     *
     * @return Collection<int, BingoPattern>
     */
    public function getByUserAndDefault(string $userId, array $fields = ['*'], ?string $status = null) : Collection;

    /**
     * Actualizar patrón
     */
    public function updatePattern(string $patternId, array $updateData) : bool;

    /**
     * Actualizar estado de patrón
     */
    public function updatePatternStatus(string $patternId, string $newStatus) : bool;
}
