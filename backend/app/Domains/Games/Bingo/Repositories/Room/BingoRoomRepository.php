<?php

namespace App\Domains\Games\Bingo\Repositories\Room;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Repositories\BaseRepository;
use App\Domains\Games\Bingo\Contracts\Room\BingoRoomRepositoryInterface;
use App\Models\Games\Common\Utils\GameStatus;
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
     * @param string $today  Fecha en formato YYYY-MM-DD
     * @param bool   $isPlayer Si el usuario tiene rol 'player'
     * @param bool   $isCommerceCustomer Si el usuario tiene rol 'commerce' o 'customer'
     * @return Collection<BingoRoom>
     */
    public function getActiveRoomsForViewWithRoleFilter(int $userId, string $today, bool $isPlayer, bool $isCommerceCustomer = false, bool $isAdmin = false): Collection
    {
        $with = [
            'config:room_id,unix_date,card_price_usd,prize_pool_type_id,date,time,room_type_id,game_mode_id,min_cards_to_start,game_variant,allow_custom_card,custom_card_price_pct',
            'config.prizePoolType:id,code,label',
            'config.roomType:id,code,label',
            'config.gameMode:id,code,label',
            'status:id,code',
        ];

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
