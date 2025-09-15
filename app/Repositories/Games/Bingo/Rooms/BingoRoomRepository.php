<?php

namespace App\Repositories\Games\Bingo;

use App\Models\Games\Bingo\Rooms\BingoRoom;
use App\Repositories\BaseRepository;
use App\Repositories\Contracts\Games\Bingo\BingoRoomRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Repositorio para operaciones con salas de Bingo
 * Maneja todas las consultas complejas relacionadas con BingoRoom
 */
class BingoRoomRepository extends BaseRepository implements BingoRoomRepositoryContract
{
    public function __construct(BingoRoom $model)
    {
        parent::__construct($model);
    }

    /**
     * Buscar sala por código encriptado
     *
     * @param string $encryptedCode Código encriptado de la sala
     * @param array $columns Columnas a seleccionar
     * @return BingoRoom|null
     */
    public function findByEncryptedCode(string $encryptedCode, array $columns = ['*']): ?BingoRoom
    {
        return $this->model->where('encrypted_code', $encryptedCode)->first($columns);
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
     * Buscar salas por tipo
     *
     * @param string $roomType Tipo de sala (PV, PB)
     * @param array $columns Columnas a seleccionar
     * @return Collection<BingoRoom>
     */
    public function findByType(string $roomType, array $columns = ['*']): Collection
    {
        return $this->model->where('room_type', $roomType)->get($columns);
    }

    /**
     * Obtener salas activas
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<BingoRoom>
     */
    public function getActiveRooms(array $columns = ['*']): Collection
    {
        return $this->model->whereHas('status', function($query) {
            $query->where('name', 'ACTIVE');
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
        return $this->model->where('prizes_paid', 'N')
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
        return $this->model->whereBetween('publication_date', [
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
     * Verificar si una sala existe por código encriptado
     *
     * @param string $encryptedCode Código encriptado
     * @return bool
     */
    public function existsByEncryptedCode(string $encryptedCode): bool
    {
        return $this->model->where('encrypted_code', $encryptedCode)->exists();
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
     * Obtener valor específico de una sala por código encriptado
     *
     * @param string $encryptedCode Código encriptado
     * @param string $column Columna a obtener
     * @return mixed
     */
    public function getRoomValueByEncrypted(string $encryptedCode, string $column): mixed
    {
        return $this->model->where('encrypted_code', $encryptedCode)->value($column);
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
            'closed_at' => Carbon::now(),
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

        if (isset($criteria['room_type'])) {
            $query->where('room_type', $criteria['room_type']);
        }

        if (isset($criteria['date_from'])) {
            $query->where('publication_date', '>=', $criteria['date_from']);
        }

        if (isset($criteria['date_to'])) {
            $query->where('publication_date', '<=', $criteria['date_to']);
        }

        if (isset($criteria['min_prize_pool'])) {
            $query->where('prize_pool', '>=', $criteria['min_prize_pool']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}