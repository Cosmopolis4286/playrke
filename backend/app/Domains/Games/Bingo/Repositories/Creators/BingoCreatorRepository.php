<?php

namespace App\Domains\Games\Bingo\Repositories\Creators;

use App\Domains\Games\Bingo\Models\Creator\BingoCreator;
use App\Domains\Games\Bingo\Repositories\Creators\Contracts\BingoCreatorRepositoryContract;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

/**
 * Repositorio para gestionar creadores de salas de bingo
 *
 * Implementa todas las operaciones de consulta y manipulación de datos
 * para los creadores de salas de bingo siguiendo el patrón Repository.
 *
 * @author Sistema PlayRKE
 *
 * @version 1.0
 */
class BingoCreatorRepository extends BaseRepository implements BingoCreatorRepositoryContract
{
    /**
     * Constructor del repositorio
     */
    public function __construct(BingoCreator $model)
    {
        $this->model = $model;
    }

    /**
     * Verificar si existe un creador para una sala y usuario específicos
     */
    public function existsByRoomAndCreatorUser(string $roomId, string $creatorUserId) : bool
    {
        return $this->model->where('room_id', $roomId)
            ->where('user_id', $creatorUserId)
            ->exists();
    }

    /**
     * Obtener creador por sala
     */
    public function findByRoom(string $roomId) : ?BingoCreator
    {
        return $this->model->where('room_id', $roomId)->first();
    }

    /**
     * Obtener creador por sala y usuario.
     *
     * bingo_room_creators no tiene columnas creator_type/creator_id;
     * $creatorType se conserva en la firma por compatibilidad con el
     * contrato mientras que $creatorId se filtra contra user_id.
     *
     * @param string $creatorType sin uso (ver nota arriba)
     * @param string $creatorId   ID del usuario creador (user_id)
     */
    public function findByRoomAndCreator(string $roomId, string $creatorType, string $creatorId) : ?BingoCreator
    {
        return $this->model->where('room_id', $roomId)
            ->where('user_id', $creatorId)
            ->first();
    }

    /**
     * Obtener creador por sala y usuario creador
     */
    public function findByRoomAndCreatorUser(string $roomId, string $creatorUserId) : ?BingoCreator
    {
        return $this->model->where('room_id', $roomId)
            ->where('user_id', $creatorUserId)
            ->first();
    }

    /**
     * Obtener creadores activos
     *
     * @return Collection<int, BingoCreator>
     */
    public function getActiveCreators() : Collection
    {
        return $this->getByStatus('ACTIVE');
    }

    /**
     * Obtener precio promedio de cartas.
     *
     * bingo_room_creators no tiene columna card_price. No hay
     * equivalente posible sin cambio de esquema.
     *
     * @throws RuntimeException
     */
    public function getAverageCardPrice() : float
    {
        throw new RuntimeException('bingo_room_creators no tiene columna card_price; usar BingoCardPriceRepository.');
    }

    /**
     * Obtener valor promedio de comisiones
     */
    public function getAverageCommission() : float
    {
        return (float) $this->model->avg('creator_commission') ?? 0.0;
    }

    /**
     * Obtener creadores por rango de precios de cartas.
     *
     * bingo_room_creators no tiene columna card_price (el precio del
     * cartón vive en bingo_card_prices, por sala/moneda). No hay
     * equivalente posible sin cambio de esquema.
     *
     * @throws RuntimeException
     */
    public function getByCardPriceRange(float $minPrice, float $maxPrice) : Collection
    {
        throw new RuntimeException('bingo_room_creators no tiene columna card_price; usar BingoCardPriceRepository.');
    }

    /**
     * Obtener creadores por ID de creador específico.
     *
     * bingo_room_creators no tiene columna creator_id; se filtra
     * contra user_id, que es el identificador real del creador.
     *
     * @param string $creatorId ID del usuario creador (user_id)
     *
     * @return Collection<int, BingoCreator>
     */
    public function getByCreatorId(string $creatorId) : Collection
    {
        return $this->model->where('user_id', $creatorId)->get();
    }

    /**
     * Obtener todos los creadores por tipo.
     *
     * bingo_room_creators no tiene columna creator_type; no existe
     * un filtro equivalente posible sin cambio de esquema. Devuelve
     * todos los registros para no romper el contrato.
     *
     * @param string $creatorType sin uso (ver nota arriba)
     *
     * @return Collection<int, BingoCreator>
     */
    public function getByCreatorType(string $creatorType) : Collection
    {
        return $this->model->get();
    }

    /**
     * Obtener creadores por usuario específico
     *
     * @return Collection<int, BingoCreator>
     */
    public function getByCreatorUser(string $creatorUserId) : Collection
    {
        return $this->model->where('user_id', $creatorUserId)->get();
    }

    /**
     * Obtener creadores que soportan una moneda específica.
     *
     * bingo_room_creators no tiene columna allowed_currencies. No hay
     * equivalente posible sin cambio de esquema.
     *
     * @throws RuntimeException
     */
    public function getBySupportedCurrency(string $currencyCode) : Collection
    {
        throw new RuntimeException('bingo_room_creators no tiene columna allowed_currencies.');
    }

    /**
     * Obtener estadísticas de comisiones por creador
     *
     * @return array{
     *     total_commission: float,
     *     average_commission: float,
     *     room_count: int
     * }
     */
    public function getCommissionStats(string $creatorId) : array
    {
        $creator = $this->model->where('user_id', $creatorId)->first();

        if (! $creator) {
            return [
                'total_commission' => 0.0,
                'average_commission' => 0.0,
                'room_count' => 0,
            ];
        }

        $roomCount = $this->model->where('user_id', $creatorId)->count();
        $totalCommission = $this->model->where('user_id', $creatorId)
            ->sum('creator_commission');
        $averageCommission = $roomCount > 0 ? $totalCommission / $roomCount : 0.0;

        return [
            'total_commission' => (float) $totalCommission,
            'average_commission' => (float) $averageCommission,
            'room_count' => $roomCount,
        ];
    }

    /**
     * Obtener estadísticas de creadores por tipo
     *
     * @return array{
     *     total_creators: int,
     *     active_creators: int,
     *     inactive_creators: int,
     *     by_type: array
     * }
     */
    public function getCreatorStats() : array
    {
        $totalCreators = $this->model->count();
        $activeCreators = $this->model->where('status', 'ACTIVE')->count();
        $inactiveCreators = $this->model->where('status', 'INACTIVE')->count();

        return [
            'total_creators' => $totalCreators,
            'active_creators' => $activeCreators,
            'inactive_creators' => $inactiveCreators,
            // bingo_room_creators no tiene columna creator_type
            'by_type' => [],
        ];
    }

    /**
     * Obtener creadores inactivos
     *
     * @return Collection<int, BingoCreator>
     */
    public function getInactiveCreators() : Collection
    {
        return $this->getByStatus('INACTIVE');
    }

    /**
     * Obtener historial de cambios de precios de un creador.
     *
     * bingo_room_creators no tiene columna card_price (ver
     * getByCardPriceRange). No hay equivalente posible sin cambio
     * de esquema.
     *
     * @throws RuntimeException
     */
    public function getPriceHistory(string $creatorId) : Collection
    {
        throw new RuntimeException('bingo_room_creators no tiene columna card_price; usar BingoCardPriceRepository.');
    }

    /**
     * Obtener creadores con mayor actividad
     *
     * @return Collection<int, BingoCreator>
     */
    public function getTopCreatorsByActivity(int $limit = 10) : Collection
    {
        return $this->model->selectRaw('creator_user_id, COUNT(*) as room_count')
            ->groupBy('creator_user_id')
            ->orderByDesc('room_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener total de salas gestionadas por un creador
     */
    public function getTotalRoomsByCreator(string $creatorUserId) : int
    {
        return $this->model->where('creator_user_id', $creatorUserId)->count();
    }

    /**
     * Verificar conflictos de creadores en una sala
     */
    public function hasCreatorConflicts(string $roomId) : bool
    {
        return $this->model->where('room_id', $roomId)->count() > 1;
    }

    /**
     * Buscar creadores por término de búsqueda
     *
     * @return Collection<int, BingoCreator>
     */
    public function searchCreators(string $searchTerm) : Collection
    {
        return $this->model->where(function($query) use ($searchTerm) {
            $query->where('user_id', 'like', "%{$searchTerm}%")
                ->orWhere('room_id', 'like', "%{$searchTerm}%");
        })->get();
    }

    /**
     * Actualizar monedas permitidas de un creador.
     *
     * bingo_room_creators no tiene columna allowed_currencies. No hay
     * equivalente posible sin cambio de esquema.
     *
     * @throws RuntimeException
     */
    public function updateAllowedCurrencies(string $creatorId, array $allowedCurrencies) : bool
    {
        throw new RuntimeException('bingo_room_creators no tiene columna allowed_currencies.');
    }

    /**
     * Actualizar precio de carta de un creador.
     *
     * bingo_room_creators no tiene columna card_price. No hay
     * equivalente posible sin cambio de esquema.
     *
     * @throws RuntimeException
     */
    public function updateCardPrice(string $creatorId, float $newPrice) : bool
    {
        throw new RuntimeException('bingo_room_creators no tiene columna card_price; usar BingoCardPriceRepository.');
    }

    /**
     * Actualizar estado de un creador
     */
    public function updateStatus(string $creatorId, string $newStatus) : bool
    {
        return $this->model->where('id', $creatorId)
            ->update(['status' => $newStatus]) > 0;
    }
}
