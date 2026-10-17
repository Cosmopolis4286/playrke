<?php

namespace App\Domains\Games\Bingo\Repositories\Room;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use App\Repositories\BaseRepository;
use App\Domains\Games\Bingo\Contracts\Room\BingoRoomRepositoryInterface;
use App\Models\Games\Common\Utils\GameStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

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
        $statusId = GameStatus::codeToId($newStatusCode);

        if (!$statusId) {
            return false;
        }

        return $this->model->where('id', $roomId)->update([
            'status_id' => $statusId,
        ]);
    }

    /**
     * Obtener salas activas de un usuario para la vista de juego (game-rooms) con filtrado por rol.
     * Si el usuario tiene rol 'player', solo devuelve salas PUBLIC.
     * Para roles commerce/customer, filtra por from_house = N.
     * Para otros roles, devuelve todas las salas activas.
     *
     * @param int    $userId ID del usuario creador
     * @param string $today  Fecha de "hoy" ya resuelta en el timezone del usuario que
     *                       consulta (ver BingoRoomContextService::resolveUserTimezone())
     *                       — no timezone del servidor, para no comparar la columna `date`
     *                       (guardada en timezone del creador de cada sala) contra un
     *                       "hoy" desalineado.
     * @param bool   $isPlayer Si el usuario tiene rol 'player'
     * @param bool   $isCommerceCustomer Si el usuario tiene rol 'commerce' o 'customer'
     * @return Collection<BingoRoom>
     */
    public function getActiveRoomsForViewWithRoleFilter(int $userId, string $today, bool $isPlayer, bool $isCommerceCustomer = false, bool $isAdmin = false, int|array|null $sellerCommerceUserId = null): Collection
    {
        // -1 día de margen (diferencias residuales entre el tz del usuario que consulta
        // y el tz de cada creador individual) + 7 días hacia adelante — antes era ±1 día,
        // demasiado angosto para salas programadas con más de 1 día de anticipación.
        $from = Carbon::parse($today)->subDay()->toDateString();
        $to   = Carbon::parse($today)->addDays(7)->toDateString();

        $query = $this->buildActiveRoomsQuery($isPlayer, $isCommerceCustomer, $isAdmin, $userId, sellerCommerceUserId: $sellerCommerceUserId)
            ->whereHas('config', fn($q) => $this->applyDateWindow($q, $from, $to, includeNullDate: true));

        return $query->orderBy(
            BingoRoomConfig::select('time')
                ->whereColumn('room_id', 'bingo_rooms.id')
                ->limit(1)
        )->get();
    }

    /**
     * Paginar salas activas para la vista de juego (game-rooms) con filtros de
     * fecha/variante reales aplicados en SQL — variante paginada de
     * getActiveRoomsForViewWithRoleFilter() para el endpoint de filtros del
     * listado de jugador (no confundir con paginateSummaryGameRooms(), que es
     * el listado admin/commerce con sus propios filtros).
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
        bool $isAdmin = false,
        int|array|null $sellerCommerceUserId = null
    ): LengthAwarePaginator {
        // Sin filtro de fecha explícito: ventana amplia (hoy → +30 días) en vez
        // de la unbounded query completa, para no escanear todo el histórico
        // futuro de salas cuando el usuario solo quiere "ver más allá de hoy".
        // Salas sin horario (date NULL, start_mode MANUAL) solo se incluyen
        // cuando el usuario NO pidió un rango de fecha explícito — no tiene
        // sentido que aparezcan al filtrar "solo el 10 de agosto", ya que no
        // tienen una fecha con la que comparar.
        //
        // El now() de aquí es solo un fallback defensivo: en el flujo real,
        // BingoRoomContextService::gameRoomsDataForRefetch() ya completa
        // $filters['date_from'] con el "hoy" resuelto en el timezone del
        // usuario (no el del servidor) antes de llamar a este método — este
        // repositorio no conoce usuarios/timezones y no debe resolverlos.
        $hasExplicitDateFilter = !empty($filters['date_from']) || !empty($filters['date_to']);
        $from = $filters['date_from'] ?? now()->toDateString();
        $to   = $filters['date_to'] ?? now()->addDays(30)->toDateString();

        $query = $this->buildActiveRoomsQuery($isPlayer, $isCommerceCustomer, $isAdmin, $userId, sellerCommerceUserId: $sellerCommerceUserId)
            ->whereHas('config', fn($q) => $this->applyDateWindow($q, $from, $to, includeNullDate: !$hasExplicitDateFilter));

        if (!empty($filters['variant'])) {
            $query->whereHas('config', fn($q) => $q->where('game_variant', $filters['variant']));
        }

        return $query->orderBy(
            BingoRoomConfig::select('time')
                ->whereColumn('room_id', 'bingo_rooms.id')
                ->limit(1)
        )->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Sala PRIVATE activa más próxima cuyo access_code coincide con el
     * indicado — candidato(s) para BingoRoomCrudService::joinRoomByCode(),
     * que desambigua por contraseña ya que access_code no tiene constraint
     * de unicidad a nivel de BD.
     */
    public function findActivePrivateRoomsByAccessCode(string $accessCode): Collection
    {
        return BingoRoom::with(['config.roomType', 'status'])
            ->whereHas('status', fn($q) => $q->whereIn('code', ['ACTIVE', 'IN_PROGRESS']))
            ->whereHas('config', function ($q) use ($accessCode) {
                $q->where('access_code', $accessCode)
                    ->whereHas('roomType', fn($rq) => $rq->where('code', 'PRIVATE'));
            })
            ->get();
    }

    /**
     * Sala activa con el inicio programado más próximo, dentro de los mismos
     * filtros de rol/fecha/variante que paginateRoomsForViewWithFilters() —
     * usada para el KPI "próxima sala" cuando hay un filtro de fecha activo
     * (fuera de ese caso, el KPI se calcula en el cliente desde el listado ya
     * cargado, sin necesidad de esta query).
     *
     * @param array{date_from?: string, date_to?: string, variant?: string} $filters
     */
    public function findNextStartingRoom(
        int $userId,
        array $filters,
        bool $isPlayer,
        bool $isCommerceCustomer = false,
        bool $isAdmin = false,
        int|array|null $sellerCommerceUserId = null
    ): ?BingoRoom {
        // now() aquí es fallback defensivo — ver comentario en
        // paginateRoomsForViewWithFilters(), mismo criterio.
        $hasExplicitDateFilter = !empty($filters['date_from']) || !empty($filters['date_to']);
        $from = $filters['date_from'] ?? now()->toDateString();
        $to   = $filters['date_to'] ?? now()->addDays(30)->toDateString();

        $query = $this->buildActiveRoomsQuery($isPlayer, $isCommerceCustomer, $isAdmin, $userId, light: true, sellerCommerceUserId: $sellerCommerceUserId)
            ->whereHas('status', fn($q) => $q->where('code', 'ACTIVE'))
            ->whereHas('config', fn($q) => $this->applyDateWindow($q, $from, $to, includeNullDate: !$hasExplicitDateFilter));

        if (!empty($filters['variant'])) {
            $query->whereHas('config', fn($q) => $q->where('game_variant', $filters['variant']));
        }

        return $query->orderBy(
            BingoRoomConfig::select('unix_date')
                ->whereColumn('room_id', 'bingo_rooms.id')
                ->limit(1)
        )->first();
    }

    /**
     * Aplica la ventana de fecha [from, to] a una query sobre bingo_rooms_config,
     * incluyendo opcionalmente las salas sin horario programado (date NULL —
     * start_mode MANUAL, "activar cuando quiera el anfitrión"). whereBetween()
     * por sí solo excluye NULLs en SQL, así que sin este OR esas salas nunca
     * aparecerían en el listado de jugador aunque estén ACTIVE.
     */
    private function applyDateWindow(Builder $query, string $from, string $to, bool $includeNullDate): void
    {
        if ($includeNullDate) {
            $query->where(function ($q) use ($from, $to) {
                $q->whereBetween('date', [$from, $to])
                    ->orWhereNull('date');
            });
            return;
        }

        $query->whereBetween('date', [$from, $to]);
    }

    /**
     * Query base compartida por getActiveRoomsForViewWithRoleFilter(),
     * paginateRoomsForViewWithFilters() y findNextStartingRoom() — resuelve
     * el filtrado por rol (jugador ve solo PUBLIC, commerce/customer solo
     * from_house=N, admin sin filtro, fallback a solo las propias) y el
     * eager loading, sin aplicar todavía el filtro de fecha/variante (cada
     * llamador lo agrega según su propio rango).
     *
     * @param bool $light Si true, omite los with() pesados (players,
     *                     playersPreview) usados solo por el listado real de
     *                     salas — las queries de agregación (KPIs) no los
     *                     necesitan y serían N+1 innecesarios.
     * @param int|array|null $sellerCommerceUserId user_id(s) dueños de las salas que vende el vendedor (Seller::roomOwnerUserIds(): comercializadora + agencia)
     *                     (Seller::resolveCommerce(), directo o vía agencia) —
     *                     si viene, el seller ve las salas de su comercio + las
     *                     PUBLIC (mismo criterio que BingoRoom::scopeAccessibleBy()
     *                     en el formulario de generar cartones). Sin esto caía en
     *                     el fallback "solo las propias" y un seller, que nunca
     *                     crea salas, veía el listado vacío.
     */
    private function buildActiveRoomsQuery(
        bool $isPlayer,
        bool $isCommerceCustomer,
        bool $isAdmin,
        int $userId,
        bool $light = false,
        int|array|null $sellerCommerceUserId = null
    ): Builder {
        $with = [
            'config:room_id,unix_date,card_price_usd,prize_pool_type_id,date,time,room_type_id,connection_type_id,min_cards_to_start,max_cards_to_start,max_players,min_players,game_variant,allow_custom_card,custom_card_price_pct,custom_card_mode,custom_card_every_x',
            'config.prizePoolType:id,code,label',
            'config.roomType:id,code,label',
            'config.connectionType:id,code,label',
            'status:id,code',
        ];

        if (!$light) {
            // Cargar cartones del jugador actual en cada sala
            if ($isPlayer) {
                $with['players'] = fn($q) => $q->where('user_id', $userId)->select(['room_id', 'user_id', 'cards_purchased']);
            }

            // Preview de jugadores (prueba social en la card del lobby): solo username/first_name,
            // nunca datos sensibles (email, teléfono). Se trunca a 4 en PHP tras cargar — sin
            // límite por-sala real en la query (HasMany no soporta limit-per-group sin window
            // functions), aceptable porque las salas no acumulan miles de jugadores. Relación
            // separada de `players` para no pisar su constraint por user_id (ver comentario arriba).
            $with['playersPreview'] = fn($q) => $q->select(['id', 'room_id', 'user_id']);
            $with['playersPreview.user'] = fn($q) => $q->select(['id', 'username', 'first_name']);
        }

        $query = $this->model
            ->with($with)
            ->whereHas('status', fn($q) => $q->whereIn('code', ['ACTIVE', 'IN_PROGRESS']))
            ->select(['id', 'uuid_code', 'prize_pool', 'total_players', 'total_cards', 'creator_user_id', 'status_id', 'created_at'])
            ->withCount('rounds');

        if ($isPlayer) {
            // Players: solo salas públicas
            $query->whereHas('config.roomType', fn($q) => $q->where('code', 'PUBLIC'));
        } elseif ($isCommerceCustomer) {
            // Commerce/customer: sus salas + las PÚBLICAS (mismo criterio que el
            // punto de venta, BingoRoom::scopeAccessibleBy). Antes veían todas las
            // salas de usuarios (from_house = N), incluidas las privadas/VIP de
            // otros comercios, que no pueden vender ni comprar.
            $query->accessibleBy($userId);
        } elseif ($isAdmin) {
            // Admin/superadmin: todas las salas sin filtro adicional
        } elseif ($sellerCommerceUserId !== null) {
            // Seller: salas de su comercio + públicas
            $query->accessibleBy($sellerCommerceUserId);
        } else {
            // Fallback: solo las salas propias
            $query->where('bingo_rooms.creator_user_id', $userId);
        }

        return $query;
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

    /**
     * {@inheritDoc}
     */
    public function paginateSummaryGameRooms(
        int $perPage,
        int $page,
        array $conditions,
        array $filters,
        array $relations,
        array $fields
    ): LengthAwarePaginator {
        $query = $this->model->newQuery()
            ->select($fields)
            ->with($relations);

        $this->applySummaryFilters($query, $conditions, $filters);

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Mismo conjunto de salas que paginateSummaryGameRooms() pero sin
     * paginar — usado por el export CSV (BingoRoomContextService::
     * exportQuery()), que necesita recorrer el resultado completo, no solo
     * una página.
     */
    public function queryForExport(array $conditions, array $filters): Builder
    {
        $query = $this->model->newQuery()
            ->with(['config.roomType', 'config.connectionType', 'config.prizePoolType', 'status', 'creator:id,username']);

        $this->applySummaryFilters($query, $conditions, $filters);

        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Condiciones (igualdad/whereIn por campo) + filtros de negocio
     * compartidos por paginateSummaryGameRooms() y queryForExport() — ver
     * BingoRoomContextService::resolveVisibilityConditions() para el origen
     * de $conditions.
     */
    private function applySummaryFilters(Builder $query, array $conditions, array $filters): void
    {
        foreach ($conditions as $field => $value) {
            // 'creator_user_id_in' (filtro por Commerce en BingoRoomContextService::
            // resolveSummaryGameRooms(), admin/superadmin) resuelve un árbol de
            // varios user_id (Commerce + sus Sellers) — requiere whereIn, no where().
            if ($field === 'creator_user_id_in') {
                $query->whereIn('creator_user_id', $value);
                continue;
            }

            $query->where($field, $value);
        }

        // Búsqueda de texto: solo contra uuid_code (única columna de texto libre
        // real en bingo_rooms) — el resto de columnas visibles en la tabla son
        // HTML/labels calculados en PHP tras traer los datos, sin equivalente
        // directo seguro en SQL.
        if (!empty($filters['search'])) {
            $query->where('uuid_code', 'like', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['status'])) {
            $query->whereHas('status', fn ($q) => $q->where('code', $filters['status']));
        }

        if (!empty($filters['room_type_id'])) {
            $query->whereHas('config', fn ($q) => $q->where('room_type_id', $filters['room_type_id']));
        }

        if (!empty($filters['connection_type_id'])) {
            $query->whereHas('config', fn ($q) => $q->where('connection_type_id', $filters['connection_type_id']));
        }

        if (!empty($filters['prize_pool_type_id'])) {
            $query->whereHas('config', fn ($q) => $q->where('prize_pool_type_id', $filters['prize_pool_type_id']));
        }

        if (!empty($filters['from_house'])) {
            $query->whereHas('config', fn ($q) => $q->where('from_house', $filters['from_house']));
        }

        if (!empty($filters['currency_id'])) {
            $query->whereHas(
                'config',
                fn ($q) => $q->whereJsonContains('allowed_currencies', (string) $filters['currency_id'])
            );
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
    }

    /**
     * Salas PUBLIC activas para la vitrina pública (sin sesión, ver
     * BingoRoomContextService::publicShowcase()) — mismo criterio de
     * visibilidad que un player autenticado (buildActiveRoomsQuery con
     * $isPlayer=true), pero sin `players` (no hay usuario real cuyos
     * cartones cargar) y con una ventana de fecha fija (hoy → +7 días,
     * mismo rango que getActiveRoomsForViewWithRoleFilter) ya que no hay
     * timezone de usuario que resolver para un visitante anónimo.
     */
    public function getActivePublicRoomsForShowcase(): Collection
    {
        $from = now()->subDay()->toDateString();
        $to   = now()->addDays(7)->toDateString();

        return $this->model
            ->with([
                'config:room_id,unix_date,card_price_usd,prize_pool_type_id,date,time,room_type_id,connection_type_id,min_cards_to_start,max_cards_to_start,max_players,min_players,game_variant',
                'config.prizePoolType:id,code,label',
                'config.roomType:id,code,label',
                'config.connectionType:id,code,label',
                'status:id,code',
                'playersPreview' => fn ($q) => $q->select(['id', 'room_id', 'user_id']),
                'playersPreview.user:id,username,first_name',
            ])
            ->whereHas('status', fn ($q) => $q->whereIn('code', ['ACTIVE', 'IN_PROGRESS']))
            ->whereHas('config.roomType', fn ($q) => $q->where('code', 'PUBLIC'))
            ->whereHas('config', fn ($q) => $this->applyDateWindow($q, $from, $to, includeNullDate: true))
            ->select(['id', 'uuid_code', 'prize_pool', 'total_players', 'total_cards', 'creator_user_id', 'status_id', 'created_at'])
            ->withCount('rounds')
            ->orderBy(
                BingoRoomConfig::select('time')
                    ->whereColumn('room_id', 'bingo_rooms.id')
                    ->limit(1)
            )
            ->get();
    }

    /**
     * Top compradores de una sala por cantidad de cartones — ver docblock en
     * BingoRoomRepositoryInterface. Agregación directa sobre bingo_cards
     * (groupBy creator_user_id), no vía relación Eloquent: no hay forma
     * limpia de pedir "top N por grupo con datos del usuario" con hasMany.
     */
    public function getTopBuyers(string $roomId, int $limit = 10): \Illuminate\Support\Collection
    {
        return \App\Domains\Games\Bingo\Models\Card\BingoCard::query()
            ->selectRaw('creator_user_id, COUNT(*) as cards_count')
            ->where('room_id', $roomId)
            ->whereNotNull('creator_user_id')
            ->groupBy('creator_user_id')
            ->orderByDesc('cards_count')
            ->limit($limit)
            ->with(['creatorUser:id,first_name,username,profile_photo_url'])
            ->get()
            ->map(fn ($row) => [
                'user_id'     => (string) $row->creator_user_id,
                'first_name'  => $row->creatorUser?->first_name,
                'username'    => $row->creatorUser?->username,
                'avatar_url'  => $row->creatorUser?->profile_photo_url,
                'cards_count' => (int) $row->cards_count,
            ])
            ->values();
    }
}
