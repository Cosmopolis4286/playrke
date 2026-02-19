<?php

namespace App\Services\Games\Bingo\Pattern;

use App\Contracts\Games\Bingo\Pattern\BingoPatternRepositoryInterface;
use App\DTOs\Games\Bingo\Pattern\BingoPatternCreateDTO;
use App\DTOs\Games\Bingo\Pattern\BingoPatternUpdateDTO;
use App\Exceptions\Domain\Games\Bingo\Pattern\BingoPatternException;
use App\Http\Resources\Games\Bingo\Pattern\BingoPatternResource;
use App\Models\Games\Bingo\Pattern\BingoPattern;
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
}
