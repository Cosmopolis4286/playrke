<?php

namespace App\Repositories\Games\Bingo\Room;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Repositories\BaseRepository;
use App\Contracts\Games\Bingo\Room\BingoRoomRepositoryInterface;
use App\Models\Games\Common\Utils\GameStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

/**
 * Repositorio para operaciones con salas de Bingo
 * Maneja todas las consultas complejas relacionadas con BingoRoom
 */
class BingoRoomRepository extends BaseRepository implements BingoRoomRepositoryInterface
{
    public function __construct(BingoRoom $model)
    {
        $this->model = $model;
    }

    /**
     * Buscar sala por código UUID
     *
     * @param string $uuidCode Código UUID de la sala
     * @param array $columns Columnas a seleccionar
     * @return BingoRoom|null
     */
    public function findByUUIDCode(string $uuidCode, array $columns = ['*']): ?BingoRoom
    {
        return $this->model->where('uuid_code', $uuidCode)->first($columns);
    }

    /**
     * Buscar salas por creador
     *
     * @param string $creatorUserId ID del usuario creador
     * @param array $columns Columnas a seleccionar
     * @return Collection<BingoRoom>
     */
    public function findByCreator(string $creatorUserId, array $columns = ['*']): Collection
    {
        return $this->model->where('creator_user_id', $creatorUserId)
            ->orderBy('created_at', 'desc')
            ->get($columns);
    }

    /**
     * Buscar salas por estado
     *
     * @param string $statusId ID del estado
     * @param array $columns Columnas a seleccionar
     * @return Collection<BingoRoom>
     */
    public function findByStatus(string $statusId, array $columns = ['*']): Collection
    {
        return $this->model->where('status_id', $statusId)->get($columns);
    }

    /**
     * Obtener salas activas
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<BingoRoom>
     */
    public function getActiveRooms(array $columns = ['*']): Collection
    {
        return $this->model->whereHas('status', function ($query) {
            $query->where('value', 'ACTIVE');
        })->get($columns);
    }

    /**
     * Obtener salas con premios pendientes de pago
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<BingoRoom>
     */
    public function getRoomsWithPendingPrizes(array $columns = ['*']): Collection
    {
        return $this->model->where('paid_prizes', 'N')
            ->where('prize_pool', '>', 0)
            ->get($columns);
    }

    /**
     * Obtener estadísticas de ventas de una sala
     *
     * @param string $roomId ID de la sala
     * @return array
     */
    public function getRoomSalesStats(string $roomId): array
    {
        $room = $this->model->with(['cards', 'players'])->find($roomId);

        if (!$room) {
            return [
                'total_sales' => 0,
                'total_players' => 0,
                'total_cards' => 0,
                'rke_commission' => 0,
                'room_commission' => 0,
                'seller_commission' => 0,
                'prize_pool' => 0,
            ];
        }

        return [
            'total_sales' => $room->total_sales,
            'total_players' => $room->total_players,
            'total_cards' => $room->total_cards,
            'rke_commission' => $room->rke_commission,
            'room_commission' => $room->room_commission,
            'seller_commission' => $room->seller_commission,
            'prize_pool' => $room->prize_pool,
        ];
    }

    /**
     * Buscar salas por rango de fechas
     *
     * @param string $startDate Fecha inicial
     * @param string $endDate Fecha final
     * @param array $columns Columnas a seleccionar
     * @return Collection<BingoRoom>
     */
    public function findByDateRange(string $startDate, string $endDate, array $columns = ['*']): Collection
    {
        return $this->model->whereBetween('published_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ])->get($columns);
    }

    /**
     * Obtener total de comisiones por sala
     *
     * @param string $roomId ID de la sala
     * @return array
     */
    public function getRoomCommissions(string $roomId): array
    {
        $room = $this->find($roomId, [
            'rke_commission',
            'room_commission',
            'seller_commission'
        ]);

        if (!$room) {
            return [
                'rke_commission' => 0,
                'room_commission' => 0,
                'seller_commission' => 0,
                'total_commission' => 0,
            ];
        }

        $total = $room->rke_commission + $room->room_commission + $room->seller_commission;

        return [
            'rke_commission' => $room->rke_commission,
            'room_commission' => $room->room_commission,
            'seller_commission' => $room->seller_commission,
            'total_commission' => $total,
        ];
    }


    /**
     * Verificar si una sala existe por código UUID
     *
     * @param string $uuidCode Código UUID de la sala
     * @return bool
     */
    public function existsByUUIDCode(string $uuidCode): bool
    {
        return $this->model->where('uuid_code', $uuidCode)->exists();
    }

    /**
     * Obtener valor específico de una sala por ID
     *
     * @param string $roomId ID de la sala
     * @param string $column Columna a obtener
     * @return mixed
     */
    public function getRoomValue(string $roomId, string $column): mixed
    {
        return $this->model->where('id', $roomId)->value($column);
    }

    /**
     * Obtener valor específico de una sala por código UUID
     *
     * @param string $uuidCode Código UUID de la sala
     * @param string $column Columna a obtener
     * @return mixed
     */
    public function getRoomValueByUUID(string $uuidCode, string $column): mixed
    {
        return $this->model->where('uuid_code', $uuidCode)->value($column);
    }

    /**
     * Cerrar una sala
     *
     * @param string $roomId ID de la sala
     * @return bool
     */
    public function closeRoom(string $roomId): bool
    {
        return $this->model->where('id', $roomId)->update([
            'finished_at' => Carbon::now(),
        ]) > 0;
    }

    /**
     * Actualizar pool de premios
     *
     * @param string $roomId ID de la sala
     * @param float $amount Monto a agregar/actualizar
     * @return bool
     */
    public function updatePrizePool(string $roomId, float $amount): bool
    {
        return $this->model->where('id', $roomId)->update([
            'prize_pool' => $amount,
        ]) > 0;
    }

    public function changeGameStatus(string $roomId, string $newStatusCode): bool
    {
        $statusId = GameStatus::where('code', $newStatusCode)->value('id');

        if (!$statusId) {
            return false;
        }

        return $this->model->where('id', $roomId)->update([
            'status_id' => $statusId,
        ]);
    }

    /**
     * Obtener salas activas de un usuario para la vista de juego (game-rooms).
     *
     * Solo devuelve salas con estado ACTIVE que tengan config programada para hoy,
     * con las relaciones necesarias para el countdown y el card display.
     *
     * @param int    $userId ID del usuario creador
     * @param string $today  Fecha en formato YYYY-MM-DD
     * @return Collection<BingoRoom>
     */
    public function getActiveRoomsForView(int $userId, string $today): Collection
    {
        return $this->model
            ->with([
                'config:room_id,unix_date,card_price_usd,prize_pool_type_id,date,time,room_type_id',
                'config.prizePoolType:id,code,label',
                'config.roomType:id,code,label',
                'status:id,code',
            ])
            ->whereHas('status', fn($q) => $q->where('code', 'ACTIVE'))
            ->whereHas('config', fn($q) => $q->whereDate('date', $today))
            ->where('bingo_rooms.creator_user_id', $userId)
            ->select(['id', 'uuid_code', 'prize_pool', 'total_players', 'creator_user_id', 'status_id'])
            ->orderBy(
                \App\Domains\Games\Bingo\Models\Room\BingoRoomConfig::select('time')
                    ->whereColumn('room_id', 'bingo_rooms.id')
                    ->limit(1)
            )
            ->get();
    }

    /**
     * Obtener salas activas de un usuario para la vista de juego (game-rooms) con filtrado por rol.
     * Si el usuario tiene rol 'player', solo devuelve salas PUBLIC.
     * Para roles commerce/customer, filtra por from_house = N.
     * Para otros roles, devuelve todas las salas activas.
     *
     * @param int    $userId ID del usuario creador
     * @param string $today  Fecha en formato YYYY-MM-DD
     * @param bool   $isPlayer Si el usuario tiene rol 'player'
     * @param bool   $isCommerceCustomer Si el usuario tiene rol 'commerce' o 'customer'
     * @return Collection<BingoRoom>
     */
    public function getActiveRoomsForViewWithRoleFilter(int $userId, string $today, bool $isPlayer, bool $isCommerceCustomer = false, bool $isAdmin = false): Collection
    {
        $with = [
            'config:room_id,unix_date,card_price_usd,prize_pool_type_id,date,time,room_type_id,game_mode_id,min_cards_to_start,game_variant',
            'config.prizePoolType:id,code,label',
            'config.roomType:id,code,label',
            'config.gameMode:id,code,label',
            'status:id,code',
        ];

        // Cargar cartones del jugador actual en cada sala
        if ($isPlayer) {
            $with['players'] = fn($q) => $q->where('user_id', $userId)->select(['room_id', 'user_id', 'cards_purchased']);
        }

        // Ventana ±1 día para cubrir diferencias de timezone entre servidor (UTC)
        // y zonas de los creadores (ej. America/Caracas UTC-4).
        // El frontend filtra visualmente por countdown expirado.
        $from = now()->subDay()->toDateString();
        $to   = now()->addDay()->toDateString();

        $query = $this->model
            ->with($with)
            ->whereHas('status', fn($q) => $q->whereIn('code', ['ACTIVE', 'IN_PROGRESS']))
            ->whereHas('config', fn($q) => $q->whereBetween('date', [$from, $to]))
            ->select(['id', 'uuid_code', 'prize_pool', 'total_players', 'total_cards', 'creator_user_id', 'status_id'])
            ->withCount('rounds');

        if ($isPlayer) {
            // Players: solo salas públicas
            $query->whereHas('config.roomType', fn($q) => $q->where('code', 'PUBLIC'));
        } elseif ($isCommerceCustomer) {
            // Commerce/customer: solo salas de usuarios (from_house = N)
            $query->whereHas('config', fn($q) => $q->where('from_house', 'N'));
        } elseif ($isAdmin) {
            // Admin/superadmin: todas las salas sin filtro adicional
        } else {
            // Fallback: solo las salas propias
            $query->where('bingo_rooms.creator_user_id', $userId);
        }

        return $query->orderBy(
            \App\Domains\Games\Bingo\Models\Room\BingoRoomConfig::select('time')
                ->whereColumn('room_id', 'bingo_rooms.id')
                ->limit(1)
        )->get();
    }

    /**
     * Obtener salas más recientes con estadísticas
     *
     * @param int $limit Límite de resultados
     * @return Collection<BingoRoom>
     */
    public function getRecentRoomsWithStats(int $limit = 10): Collection
    {
        return $this->model->with(['status', 'creator'])
            ->withCount('cards')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Buscar salas por múltiples criterios
     *
     * @param array $criteria Criterios de búsqueda
     * @return Collection<BingoRoom>
     */
    public function searchRooms(array $criteria): Collection
    {
        $query = $this->model->query();

        if (isset($criteria['creator_id'])) {
            $query->where('creator_user_id', $criteria['creator_id']);
        }

        if (isset($criteria['status_id'])) {
            $query->where('status_id', $criteria['status_id']);
        }

        if (isset($criteria['date_from'])) {
            $query->where('published_at', '>=', $criteria['date_from']);
        }

        if (isset($criteria['date_to'])) {
            $query->where('published_at', '<=', $criteria['date_to']);
        }

        if (isset($criteria['min_prize_pool'])) {
            $query->where('prize_pool', '>=', $criteria['min_prize_pool']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Override paginateWithRelations to add ordering by creation date
     *
     * @param int $perPage
     * @param int $page
     * @param array $relations
     * @param array $conditions
     * @param array $fields
     * @return LengthAwarePaginator
     */
    public function paginateWithRelations(
        int $perPage = 15,
        int $page = 1,
        array $relations = [],
        array $conditions = [],
        array $fields = ['*']
    ): LengthAwarePaginator {
        $query = $this->model->newQuery();

        // Seleccionar columnas específicas
        $query->select($fields);

        // Cargar relaciones
        if (!empty($relations)) {
            $query->with($relations);
        }

        // Aplicar condiciones where (AND)
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                // Por ejemplo ['field' => ['operator', 'value']]
                [$operator, $val] = $value;
                $query->where($field, $operator, $val);
            } else {
                $query->where($field, $value);
            }
        }

        // Ordenar por fecha de creación (más recientes primero)
        $query->orderBy('created_at', 'desc');

        // Retornar paginación
        return $query->paginate(
            $perPage,
            ['*'],
            'page',
            $page
        );
    }
}
