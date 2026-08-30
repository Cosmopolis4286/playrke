<?php

namespace App\Domains\Games\Bingo\Contracts\Room;

use App\Contracts\BaseRepositoryInterface;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Contrato para el repositorio de salas de Bingo.
 *
 * Define métodos específicos de dominio para operaciones con BingoRoom
 * más allá del CRUD base proporcionado por BaseRepositoryInterface.
 */
interface BingoRoomRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Verificar si una sala existe por código UUID
     *
     * @param string $uuidCode Código UUID de la sala
     * @return bool
     */
    public function existsByUUIDCode(string $uuidCode): bool;

    /**
     * Actualizar pool de premios
     *
     * @param string $roomId ID de la sala
     * @param float $amount Monto a agregar/actualizar
     * @return bool
     */
    public function updatePrizePool(string $roomId, float $amount): bool;

    public function changeGameStatus(string $roomId,string $newStatusCode): bool;

    /**
     * Obtener salas activas de un usuario para la vista de juego (game-rooms) con filtrado por rol.
     * Si el usuario tiene rol 'player', solo devuelve salas PUBLIC.
     * Para roles commerce/customer, filtra por from_house = N.
     * Para otros roles, devuelve todas las salas activas.
     *
     * @param int    $userId ID del usuario creador
     * @param string $today  Fecha de "hoy" ya resuelta en el timezone del usuario que
     *                       consulta (no el timezone del servidor)
     * @param bool   $isPlayer Si el usuario tiene rol 'player'
     * @param bool   $isCommerceCustomer Si el usuario tiene rol 'commerce' o 'customer'
     * @return Collection<BingoRoom>
     */
    public function getActiveRoomsForViewWithRoleFilter(int $userId, string $today, bool $isPlayer, bool $isCommerceCustomer = false, bool $isAdmin = false): Collection;

    /**
     * Paginar salas activas para la vista de juego (game-rooms) con filtros de
     * fecha/variante reales aplicados en SQL.
     *
     * @param array{date_from?: string, date_to?: string, variant?: string} $filters
     */
    public function paginateRoomsForViewWithFilters(
        int $userId,
        array $filters,
        int $page,
        int $perPage,
        bool $isPlayer,
        bool $isCommerceCustomer = false,
        bool $isAdmin = false
    ): LengthAwarePaginator;

    /**
     * Sala(s) PRIVATE activa(s) cuyo access_code coincide con el indicado.
     * access_code no tiene constraint de unicidad, así que puede devolver más
     * de una fila — el llamador desambigua por contraseña.
     */
    public function findActivePrivateRoomsByAccessCode(string $accessCode): Collection;

    /**
     * Sala activa con el inicio programado más próximo dentro de los filtros
     * de rol/fecha/variante indicados, o null si no hay ninguna.
     *
     * @param array{date_from?: string, date_to?: string, variant?: string} $filters
     */
    public function findNextStartingRoom(
        int $userId,
        array $filters,
        bool $isPlayer,
        bool $isCommerceCustomer = false,
        bool $isAdmin = false
    ): ?BingoRoom;

    /**
     * Paginar el listado admin/commerce de salas (Room/Summary) con filtros
     * reales aplicados en SQL, en vez de traer una sola página fija y filtrar
     * en el cliente.
     *
     * @param array<string,mixed> $conditions Condiciones directas sobre bingo_rooms (campo => valor)
     * @param array{
     *     search?: string,
     *     status?: string,
     *     room_type_id?: string,
     *     connection_type_id?: string,
     *     prize_pool_type_id?: string,
     *     from_house?: string,
     *     currency_id?: string,
     * } $filters Filtros sobre relaciones (config.*, status.code) + búsqueda por uuid_code
     * @param array<int,string|\Closure> $relations
     * @param array<int,string> $fields
     */
    public function paginateSummaryGameRooms(
        int $perPage,
        int $page,
        array $conditions,
        array $filters,
        array $relations,
        array $fields
    ): LengthAwarePaginator;

    /**
     * Salas PUBLIC activas para la vitrina pública (sin sesión) — ver
     * BingoRoomRepository::getActivePublicRoomsForShowcase().
     */
    public function getActivePublicRoomsForShowcase(): Collection;

    /**
     * Top compradores de una sala por cantidad de cartones (prueba social,
     * "Top de Compras" — ver GameRooms.vue). Agrega bingo_cards.creator_user_id,
     * solo cartones con dueño real (creator_user_id no nulo — excluye lotes
     * generados por el sistema/casa).
     *
     * @return \Illuminate\Support\Collection<int, array{user_id: string, first_name: ?string, username: ?string, avatar_url: ?string, cards_count: int}>
     */
    public function getTopBuyers(string $roomId, int $limit = 10): \Illuminate\Support\Collection;
}
