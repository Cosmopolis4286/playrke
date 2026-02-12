<?php

namespace App\Services\Games\Bingo\Room;

final class BingoRoomService
{
    public function __construct(
        private readonly \App\Services\Games\Bingo\Room\BingoRoomContextService $contextService
    ) {}

    /**
     * Resolver contexto para BingoRoom.
     *
     * @param string $context
     * @param int|null $userId
     * @return mixed
     * @throws DomainException Si el contexto no es válido
     */
    public function context(string $context, ?int $userId): mixed
    {
        return $this->contextService->context($context, $userId);
    }
}
