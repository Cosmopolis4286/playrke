<?php

namespace App\Services\Games\Bingo\Room\Automatic;

use App\Domains\Games\Bingo\DTOs\Room\Automatic\BingoRoomAutomaticCreateDTO;
use App\Domains\Games\Bingo\DTOs\Room\Automatic\BingoRoomAutomaticUpdateDTO;
use App\Http\Resources\Games\Bingo\Room\Automatic\BingoRoomAutomaticResource;
use App\Domains\Games\Bingo\Models\Room\BingoRoomAutomatic;

final class BingoRoomAutomaticService
{
    public function __construct(
        private readonly BingoRoomAutomaticContextService $contextService,
        private readonly BingoRoomAutomaticCrudService $crudService,
        private readonly \App\Services\Security\UserContextService $userContext,
    ) {}

    /**
     * Resolver contexto para BingoRoomAutomatic.
     *
     * @param string $context
     * @param int|null $userId
     * @return array
     * @throws \BingoRoomAutomaticException Si el contexto no es válido
     */
    public function context(string $context, ?int $userId = null): array
    {
        return $this->contextService->context($context, $userId)->toView();
    }

    /**
     * Obtener listado de salas filtrado por contexto del usuario.
     */
    public function fetchList(): array
    {
        $userId = $this->userContext->resolveUserId();
        return $this->contextService->context('fetch', $userId);
    }

    /**
     * Mostrar detalle de una sala de bingo.
     */
    public function show(BingoRoomAutomatic $room): BingoRoomAutomaticResource
    {
        return $this->crudService->show($room);
    }

    /**
     * Crear una nueva sala de bingo con su configuración y creador.
     *
     * @param BingoRoomAutomaticCreateDTO $dto
     * @return bool
     */
    public function create(BingoRoomAutomaticCreateDTO $dto): bool
    {
        return $this->crudService->saveRoom($dto);
    }

    /**
     * Actualizar una sala de bingo existente.
     *
     * @param BingoRoomAutomatic $room Model
     * @param BingoRoomAutomaticUpdateDTO $dto
     * @return bool
     */
    public function update(BingoRoomAutomatic $room, BingoRoomAutomaticUpdateDTO $dto): bool
    {
        return $this->crudService->saveRoom($room, $dto);
    }

    /**
     * Copiar una sala de bingo existente (status INACTIVE).
     *
     * @param BingoRoomAutomatic $room Model
     * @return bool
     */
    public function copy(BingoRoomAutomatic $room): bool
    {
        return $this->crudService->copyRoom($room);
    }

    /**
     * Eliminar una sala de bingo (soft delete).
     *
     * @param BingoRoomAutomatic $room Model
     * @return bool
     *
     * @throws BingoRoomAutomaticException Si falla la eliminación
     */
    public function delete(BingoRoomAutomatic $room): bool
    {
        return $this->crudService->delete($room);
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) de una sala de bingo.
     *
     * @param BingoRoomAutomatic $room Model
     * @return bool
     *
     * @throws BingoRoomException Si falla el cambio de estado
     */
    public function toggleStatus(BingoRoomAutomatic $room): bool
    {
        return $this->crudService->toggleStatus($room);
    }

    /**
     * Eliminar una sala de bingo permanentemente (hard delete).
     */
    public function destroy(BingoRoomAutomatic $room): bool
    {
        return $room->forceDelete();
    }
}
