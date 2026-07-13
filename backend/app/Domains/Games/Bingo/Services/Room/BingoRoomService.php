<?php

namespace App\Domains\Games\Bingo\Services\Room;

use App\Domains\Games\Bingo\DTOs\Room\BingoRoomCreateDTO;
use App\Domains\Games\Bingo\DTOs\Room\BingoRoomUpdateDTO;
use App\Http\Resources\Games\Bingo\Room\BingoRoomResource;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Room\Utils\CheckExpiredBingoRoomsService;

final class BingoRoomService
{
    public function __construct(
        private readonly BingoRoomContextService $contextService,
        private readonly BingoRoomCrudService $crudService,
        private readonly \App\Services\Security\UserContextService $userContext,
        private readonly CheckExpiredBingoRoomsService $checkExpired,
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
        $this->checkExpired->handle();
        return $this->contextService->context($context, $userId)->toView();
    }

    /**
     * Obtener listado de salas filtrado por contexto del usuario.
     */
    public function fetchList(): array
    {
        $this->checkExpired->handle();
        $userId = $this->userContext->resolveUserId();
        return $this->contextService->context('fetch-summary', $userId);
    }

    /**
     * Refetch paginado/filtrado del listado admin/commerce de salas
     * (Room/Summary), usado por la tabla en modo server-side.
     *
     * @param array{
     *     status?: string,
     *     room_type_id?: string,
     *     game_mode_id?: string,
     *     prize_pool_type_id?: string,
     *     from_house?: string,
     *     currency_id?: string,
     * } $filters
     */
    public function summaryData(int $page, int $perPage, array $filters): array
    {
        $this->checkExpired->handle();
        $userId = $this->userContext->resolveUserId();
        return $this->contextService->summaryDataForRefetch($userId, $page, $perPage, $filters);
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
        return $this->crudService->saveRoomOrSplit($dto);
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
     * Copiar una sala de bingo existente (status INACTIVE).
     *
     * @param BingoRoom $room Model
     * @return bool
     */
    public function copy(BingoRoom $room): bool
    {
        return $this->crudService->copyRoom($room);
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
     * Cancelar una sala de bingo (cambiar estado a CANCELLED).
     * Solo se puede cancelar si la sala está ACTIVE y tiene jugadores.
     *
     * @param BingoRoom $room Model
     * @return bool
     *
     * @throws BingoRoomException Si falla la cancelación
     */
    public function cancel(BingoRoom $room): bool
    {
        return $this->crudService->cancel($room);
    }

    /**
     * Activar manualmente el sorteo de una sala sin horario programado (start_mode = MANUAL).
     * Solo el creador de la sala o un admin/superadmin pueden ejecutarla.
     *
     * @param BingoRoom $room Model
     * @return bool
     *
     * @throws BingoRoomException Si la sala no admite activación manual o el usuario no está autorizado
     */
    public function activateNow(BingoRoom $room): bool
    {
        return $this->crudService->activateNow($room);
    }

    /**
     * Programa la fecha/hora de inicio de una sala sin horario (start_mode = MANUAL),
     * convirtiéndola en una sala programada (start_mode = SCHEDULED). Solo el creador
     * de la sala o un admin/superadmin pueden ejecutarla.
     *
     * @param BingoRoom $room Model
     * @param string $date Fecha en formato Y-m-d
     * @param string $time Hora en formato H:i
     * @return bool
     *
     * @throws BingoRoomException Si la sala no admite programación, el usuario no está
     *                            autorizado, el estado no es válido, o la fecha/hora es inválida
     */
    public function scheduleStart(BingoRoom $room, string $date, string $time): bool
    {
        return $this->crudService->scheduleStart($room, $date, $time);
    }

    /**
     * Eliminar una sala de bingo permanentemente (hard delete).
     */
    public function destroy(BingoRoom $room): bool
    {
        return $room->forceDelete();
    }
}
