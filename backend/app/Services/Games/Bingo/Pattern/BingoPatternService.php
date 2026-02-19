<?php

namespace App\Services\Games\Bingo\Pattern;

use App\DTOs\Games\Bingo\Pattern\BingoPatternCreateDTO;
use App\DTOs\Games\Bingo\Pattern\BingoPatternUpdateDTO;
use App\Http\Resources\Games\Bingo\Pattern\BingoPatternResource;
use App\Models\Games\Bingo\Pattern\BingoPattern;

final class BingoPatternService
{
    public function __construct(
        private readonly BingoPatternContextService $contextService,
        private readonly BingoPatternCrudService $crudService,
        private readonly \App\Services\Security\UserContextService $userContext,
    ) {}

    /** Resolver contexto para BingoRoom.
     *
     * @param string $context
     * @param int|null $userId
     * @return array
     * @throws \BingoPatternException Si el contexto no es válido
     */
    public function context(string $context, ?int $userId = null): array
    {
        return $this->contextService->context($context, $userId)->toView();
    }

    /**
     * Obtener listado de patrones de bingo filtrado por contexto del usuario.
     */
    public function fetchList(): array
    {
        $userId = $this->userContext->resolveUserId();
        return $this->contextService->context('fetch', $userId);
    }

    /**
     * Mostrar detalle de patron de bingo.
     */
    public function show(BingoPattern $pattern): BingoPatternResource
    {
        return $this->crudService->show($pattern);
    }

    /**
     * Crear un patron de Bingo.
     *
     * @param BingoPatternCreateDTO $dto
     * @return bool
     */
    public function create(BingoPatternCreateDTO $dto): bool
    {
        return $this->crudService->saveRoom($dto);
    }

    /**
     * Actualizar un patron de bingo existente.
     *
     * @param BingoPattern $pattern Model
     * @param BingoPatternUpdateDTO $dto
     * @return bool
     */
    public function update(BingoPattern $pattern, BingoPatternUpdateDTO $dto): bool
    {
        return $this->crudService->saveRoom($pattern, $dto);
    }

    /**
     * Eliminar patron de bingo (soft delete).
     *
     * @param BingoPattern $pattern Model
     * @return bool
     *
     * @throws BingoPatternException Si falla la eliminación
     */
    public function delete(BingoPattern $pattern): bool
    {
        return $this->crudService->delete($pattern);
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) de un patron de bingo.
     *
     * @param BingoPattern $pattern Model
     * @return bool
     *
     * @throws BingoPatternException Si falla el cambio de estado
     */
    public function toggleStatus(BingoPattern $pattern): bool
    {
        return $this->crudService->toggleStatus($pattern);
    }

    /**
     * Eliminar patron de bingo permanentemente (hard delete).
     */
    public function destroy(BingoPattern $pattern): bool
    {
        return $pattern->forceDelete();
    }
}
