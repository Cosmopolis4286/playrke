<?php

namespace App\Services\Games\Bingo\Room;

use App\DTOs\Games\Bingo\Room\BingoRoomCreateDTO;
use App\DTOs\Games\Bingo\Room\BingoRoomUpdateDTO;
use App\Http\Resources\Games\Bingo\Room\BingoRoomResource;
use App\Models\Games\Bingo\Room\BingoRoom;

final class BingoRoomService
{
    public function __construct(
        private readonly BingoRoomContextService $contextService,
        private readonly BingoRoomCrudService $crudService,
        private readonly \App\Services\Security\UserContextService $userContext,
    ) {}

    /**
     * Resolver contexto para BingoRoom.
     *
     * @param string $context
     * @param int|null $userId
     * @return array
     * @throws \BingoRoomException Si el contexto no es válido
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
    public function show(BingoRoom $room): BingoRoomResource
    {
        return $this->crudService->show($room);
    }

    /**
     * Crear una nueva sala de bingo con su configuración y creador.
     *
     * @param BingoRoomCreateDTO $dto
     * @return bool
     */
    public function create(BingoRoomCreateDTO $dto): bool
    {
        return $this->crudService->saveRoom($dto);
    }

    /**
     * Actualizar una sala de bingo existente.
     *
     * @param BingoRoom $room Model
     * @param BingoRoomUpdateDTO $dto
     * @return bool
     */
    public function update(BingoRoom $room, BingoRoomUpdateDTO $dto): bool
    {
        return $this->crudService->saveRoom($room, $dto);
    }

    /**
     * Eliminar una sala de bingo (soft delete).
     *
     * @param BingoRoom $room Model
     * @return bool
     *
     * @throws BingoRoomException Si falla la eliminación
     */
    public function delete(BingoRoom $room): bool
    {
        return $this->crudService->delete($room);
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) de una sala de bingo.
     *
     * @param BingoRoom $room Model
     * @return bool
     *
     * @throws BingoRoomException Si falla el cambio de estado
     */
    public function toggleStatus(BingoRoom $room): bool
    {
        return $this->crudService->toggleStatus($room);
    }

    /**
     * Eliminar una sala de bingo permanentemente (hard delete).
     */
    public function destroy(BingoRoom $room): bool
    {
        return $room->forceDelete();
    }
}
