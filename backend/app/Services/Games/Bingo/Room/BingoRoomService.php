<?php

namespace App\Services\Games\Bingo\Room;

use App\DTOs\Games\Bingo\Room\BingoRoomCreateDTO;
use App\DTOs\Games\Bingo\Room\BingoRoomUpdateDTO;

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
     * @param BingoRoomUpdateDTO $dto
     * @return array
     */
    public function update(BingoRoomUpdateDTO $dto): array
    {
        return $this->crudService->update($dto);
    }
}
