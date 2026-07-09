<?php

namespace App\Domains\Games\Bingo\Services\Pattern;

use App\Domains\Games\Bingo\DTOs\Pattern\BingoPatternCreateDTO;
use App\Domains\Games\Bingo\DTOs\Pattern\BingoPatternUpdateDTO;
use App\Http\Resources\Games\Bingo\Pattern\BingoPatternResource;
use App\Domains\Games\Bingo\Models\Pattern\BingoPattern;

final class BingoPatternService
{
    public function __construct(
        private readonly BingoPatternContextService $contextService,
        private readonly BingoPatternCrudService $crudService,
        private readonly \App\Services\Security\UserContextService $userContext,
    ) {}

    /**
     * Resolver contexto para la página de patrones.
     */
    public function context(string $context, ?int $userId = null): array
    {
        return $this->contextService->context($context, $userId)->toView();
    }

    /**
     * Obtener listado de patrones filtrado por contexto del usuario.
     */
    public function fetchList(): array
    {
        $userId = $this->userContext->resolveUserId();
        return $this->contextService->fetchList($userId);
    }

    /**
     * Mostrar detalle de patrón de bingo.
     */
    public function show(BingoPattern $pattern): BingoPatternResource
    {
        return $this->crudService->show($pattern);
    }

    /**
     * Crear un patrón de bingo.
     */
    public function create(BingoPatternCreateDTO $dto): bool
    {
        return $this->crudService->create($dto);
    }

    /**
     * Actualizar un patrón de bingo existente.
     */
    public function update(BingoPattern $pattern, BingoPatternUpdateDTO $dto): bool
    {
        return $this->crudService->update($pattern, $dto);
    }

    /**
     * Eliminación lógica de un patrón (soft delete).
     */
    public function delete(BingoPattern $pattern): bool
    {
        return $this->crudService->delete($pattern);
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) de un patrón.
     */
    public function toggleStatus(BingoPattern $pattern): bool
    {
        return $this->crudService->toggleStatus($pattern);
    }

    /**
     * Eliminación permanente (hard delete).
     */
    public function destroy(BingoPattern $pattern): bool
    {
        return $pattern->forceDelete();
    }

    /**
     * Alterna el estado de múltiples patrones en un solo UPDATE bulk.
     *
     * @param (string|int)[] $ids
     */
    public function toggleStatusBulk(array $ids): int
    {
        return $this->crudService->toggleStatusBulk($ids);
    }

    /**
     * Eliminación lógica bulk de patrones.
     *
     * @param (string|int)[] $ids
     */
    public function deleteBulk(array $ids): int
    {
        return $this->crudService->deleteBulk($ids);
    }
}
