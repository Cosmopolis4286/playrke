<?php

namespace App\Services\Games\Bingo\Room;

use App\DTOs\Games\Bingo\Room\BingoRoomCreateDTO;
use App\DTOs\Games\Bingo\Room\BingoRoomUpdateDTO;
use App\Models\Games\Bingo\Room\BingoRoom;

final class BingoRoomService
{
    public function __construct(
        private readonly BingoRoomContextService $contextService,
        private readonly BingoRoomCrudService $crudService,
    ) {}

    /**
     * Resolver contexto para BingoRoom.
     *
     * @param string $context
     * @param int|null $userId
     * @return array
     * @throws \BingoRoomException Si el contexto no es válido
     */
    public function context(string $context, ?int $userId): array
    {
        return $this->contextService->context($context, $userId)->toView();
    }

    /**
     * Crear una nueva sala de bingo con su configuración y creador.
     *
     * @param BingoRoomCreateDTO $dto
     * @return bool
     */
    public function create(BingoRoomCreateDTO $dto): bool
    {
        return $this->crudService->create($dto);
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
        return $this->crudService->update($room, $dto);
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
}
