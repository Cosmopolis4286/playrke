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
     * @return mixed
     * @throws \DomainException Si el contexto no es válido
     */
    public function context(string $context, ?int $userId): mixed
    {
        return $this->contextService->context($context, $userId);
    }

    /**
     * Crear una nueva sala de bingo con su configuración y creador.
     *
     * @param BingoRoomCreateDTO $dto
     * @return array
     */
    public function create(BingoRoomCreateDTO $dto): array
    {
        return $this->crudService->create($dto);
    }

    /**
     * Actualizar una sala de bingo existente.
     *
     * @param BingoRoom $room Model
     * @param BingoRoomUpdateDTO $dto
     * @return array
     */
    public function update(BingoRoom $room, BingoRoomUpdateDTO $dto): array
    {
        return $this->crudService->update($room, $dto);
    }

    /**
     * Eliminar una sala de bingo (soft delete).
     *
     * @param BingoRoom $room Model
     *
     * @throws BingoRoomException Si falla la eliminación
     */
    public function delete(BingoRoom $room): void
    {
        $this->crudService->delete($room);
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) de una sala de bingo.
     *
     * @param BingoRoom $room Model
     *
     * @throws BingoRoomException Si falla el cambio de estado
     */
    public function toggleStatus(BingoRoom $room): void
    {
        $this->crudService->toggleStatus($room);
    }
}
