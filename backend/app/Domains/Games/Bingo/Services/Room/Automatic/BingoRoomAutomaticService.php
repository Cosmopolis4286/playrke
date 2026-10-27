<?php

namespace App\Domains\Games\Bingo\Services\Room\Automatic;

use App\Domains\Games\Bingo\DTOs\Room\Automatic\BingoRoomAutomaticCreateDTO;
use App\Domains\Games\Bingo\DTOs\Room\Automatic\BingoRoomAutomaticUpdateDTO;
use App\Domains\Games\Bingo\Models\Room\BingoRoomAutomatic;
use App\Http\Resources\Games\Bingo\Room\Automatic\BingoRoomAutomaticResource;

final class BingoRoomAutomaticService
{
    public function __construct(
        private readonly BingoRoomAutomaticContextService $contextService,
        private readonly BingoRoomAutomaticCrudService $crudService,
        private readonly \App\Services\Security\UserContextService $userContext,
    ) {
    }

    /**
     * Resolver contexto para BingoRoomAutomatic.
     *
     * @throws \BingoRoomAutomaticException Si el contexto no es válido
     */
    public function context(string $context, ?int $userId = null) : array
    {
        return $this->contextService->context($context, $userId)->toView();
    }

    /**
     * Copiar una sala de bingo existente (status INACTIVE).
     *
     * @param BingoRoomAutomatic $room Model
     */
    public function copy(BingoRoomAutomatic $room) : bool
    {
        return $this->crudService->copyRoom($room);
    }

    /**
     * Crear una nueva sala de bingo con su configuración y creador.
     */
    public function create(BingoRoomAutomaticCreateDTO $dto) : bool
    {
        return $this->crudService->saveRoom($dto);
    }

    /**
     * Eliminar una sala de bingo (soft delete).
     *
     * @param BingoRoomAutomatic $room Model
     *
     * @throws BingoRoomAutomaticException Si falla la eliminación
     */
    public function delete(BingoRoomAutomatic $room) : bool
    {
        return $this->crudService->delete($room);
    }

    /**
     * Eliminar una sala de bingo permanentemente (hard delete).
     */
    public function destroy(BingoRoomAutomatic $room) : bool
    {
        return $room->forceDelete();
    }

    /**
     * Obtener listado de salas filtrado por contexto del usuario.
     */
    public function fetchList() : array
    {
        $userId = $this->userContext->resolveUserId();

        return $this->contextService->context('fetch', $userId);
    }

    /**
     * Mostrar detalle de una sala de bingo.
     */
    public function show(BingoRoomAutomatic $room) : BingoRoomAutomaticResource
    {
        return $this->crudService->show($room);
    }

    /**
     * Refetch paginado/filtrado del listado admin/commerce de salas
     * automáticas (Room/Automatic/Summary), usado por la tabla en modo
     * server-side.
     *
     * @param array{
     *     status?: string,
     *     room_type_id?: string,
     *     connection_type_id?: string,
     *     prize_pool_type_id?: string,
     *     currency_id?: string,
     * } $filters
     */
    public function summaryData(int $page, int $perPage, array $filters) : array
    {
        $userId = $this->userContext->resolveUserId();

        return $this->contextService->summaryDataForRefetch($userId, $page, $perPage, $filters);
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) de una sala de bingo.
     *
     * @param BingoRoomAutomatic $room Model
     *
     * @throws BingoRoomException Si falla el cambio de estado
     */
    public function toggleStatus(BingoRoomAutomatic $room) : bool
    {
        return $this->crudService->toggleStatus($room);
    }

    /**
     * Actualizar una sala de bingo existente.
     *
     * @param BingoRoomAutomatic $room Model
     */
    public function update(BingoRoomAutomatic $room, BingoRoomAutomaticUpdateDTO $dto) : bool
    {
        return $this->crudService->saveRoom($room, $dto);
    }
}
