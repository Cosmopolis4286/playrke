<?php

namespace App\Domains\Games\Bingo\Services\Pattern;

use App\Domains\Games\Bingo\Contracts\Pattern\BingoPatternRepositoryInterface;
use App\Domains\Games\Bingo\DTOs\Pattern\BingoPatternCreateDTO;
use App\Domains\Games\Bingo\DTOs\Pattern\BingoPatternUpdateDTO;
use App\Domains\Games\Bingo\Exceptions\Pattern\BingoPatternException;
use App\Http\Resources\Games\Bingo\Pattern\BingoPatternResource;
use App\Domains\Games\Bingo\Models\Pattern\BingoPattern;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de aplicación para operaciones CRUD de patrones de bingo.
 *
 * @final
 */
final class BingoPatternCrudService
{
    public function __construct(
        private readonly BingoPatternRepositoryInterface $repository,
    ) {}

    /**
     * Obtener patrón para visualización.
     */
    public function show(BingoPattern $pattern): BingoPatternResource
    {
        if (!$pattern->exists) {
            throw BingoPatternException::notFound();
        }

        return new BingoPatternResource($pattern);
    }

    /**
     * Crear un nuevo patrón de bingo.
     */
    public function create(BingoPatternCreateDTO $dto): bool
    {
        $userId = Auth::id();

        if ($this->repository->existsByNameAndUser($dto->name, (string) $userId)) {
            throw BingoPatternException::duplicateName();
        }

        $this->repository->createPattern([
            'num_id'          => BingoPattern::nextNumIdForUser((string) $userId),
            'name'            => $dto->name,
            'type_pattern'    => $dto->typePattern,
            'boxes'           => $dto->boxes,
            'default_pattern' => $dto->defaultPattern,
            'user_id'         => $userId,
            'status'          => BingoPattern::STATUS_ACTIVE,
        ]);

        return true;
    }

    /**
     * Actualizar un patrón existente.
     */
    public function update(BingoPattern $pattern, BingoPatternUpdateDTO $dto): bool
    {
        if (!$pattern->exists) {
            throw BingoPatternException::notFound();
        }

        return $this->repository->updatePattern($pattern->id, [
            'name'            => $dto->name,
            'type_pattern'    => $dto->typePattern,
            'boxes'           => $dto->boxes,
            'default_pattern' => $dto->defaultPattern,
        ]);
    }

    /**
     * Alternar estado de un patrón (ACTIVE / INACTIVE).
     */
    public function toggleStatus(BingoPattern $pattern): bool
    {
        $newStatus = $pattern->status === BingoPattern::STATUS_ACTIVE
            ? BingoPattern::STATUS_INACTIVE
            : BingoPattern::STATUS_ACTIVE;

        return $this->repository->updatePatternStatus($pattern->id, $newStatus);
    }

    /**
     * Eliminación lógica (soft delete: cambia estado a INACTIVE).
     */
    public function delete(BingoPattern $pattern): bool
    {
        return $this->repository->updatePatternStatus($pattern->id, BingoPattern::STATUS_INACTIVE);
    }

    /**
     * Cambia el estado de múltiples patrones en un solo UPDATE bulk.
     *
     * @param (string|int)[] $ids
     */
    public function toggleStatusBulk(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        $activeIds   = BingoPattern::whereIn('id', $ids)->where('status', BingoPattern::STATUS_ACTIVE)->pluck('id');
        $inactiveIds = BingoPattern::whereIn('id', $ids)->where('status', BingoPattern::STATUS_INACTIVE)->pluck('id');

        $count = 0;
        if ($activeIds->isNotEmpty()) {
            BingoPattern::whereIn('id', $activeIds)->update(['status' => BingoPattern::STATUS_INACTIVE]);
            $count += $activeIds->count();
        }
        if ($inactiveIds->isNotEmpty()) {
            BingoPattern::whereIn('id', $inactiveIds)->update(['status' => BingoPattern::STATUS_ACTIVE]);
            $count += $inactiveIds->count();
        }

        return $count;
    }

    /**
     * Elimina lógicamente múltiples patrones en un solo UPDATE bulk.
     *
     * @param (string|int)[] $ids
     */
    public function deleteBulk(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        return BingoPattern::whereIn('id', $ids)->update(['status' => BingoPattern::STATUS_INACTIVE]);
    }
}
