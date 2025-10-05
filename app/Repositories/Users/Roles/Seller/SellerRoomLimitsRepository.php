<?php

namespace App\Repositories\Users\Roles\Seller;

use App\Models\Users\Roles\Seller\SellerRoomLimits;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Users\Roles\Seller\Contracts\SellerRoomLimitsRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repositorio para la gestión de límites de salas de vendedores.
 *
 * Implementa las operaciones específicas para el modelo SellerRoomLimits,
 * incluyendo gestión de límites por vendedor, sala y juego, así como
 * operaciones de comisiones y accesos.
 */
class SellerRoomLimitsRepository extends BaseRepository implements SellerRoomLimitsRepositoryContract
{
    /**
     * Constructor del repositorio.
     *
     * @param SellerRoomLimits $model Instancia del modelo SellerRoomLimits
     */
    public function __construct(SellerRoomLimits $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function getBySeller(string $sellerId): Collection
    {
        return $this->model->where('seller', $sellerId)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByRoom(string $roomId): Collection
    {
        return $this->model->where('room', $roomId)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByGame(string $gameId): Collection
    {
        return $this->model->where('game', $gameId)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByGameRoomSeller(string $gameId, string $roomId, string $sellerId): mixed
    {
        return $this->model->where('game', $gameId)
            ->where('room', $roomId)
            ->where('seller', $sellerId)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getValueByGameRoomSeller(string $gameId, string $roomId, string $sellerId, string $field): mixed
    {
        return $this->model->where('game', $gameId)
            ->where('room', $roomId)
            ->where('seller', $sellerId)
            ->value($field);
    }

    /**
     * {@inheritDoc}
     */
    public function createOrUpdateLimit(array $data): mixed
    {
        // Validar datos antes de crear/actualizar
        $errors = $this->validateLimitData($data);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Datos de límite inválidos: ' . json_encode($errors));
        }

        return $this->model->updateOrCreate(
            [
                'game' => $data['game'],
                'room' => $data['room'],
                'seller' => $data['seller']
            ],
            $data
        );
    }

    /**
     * {@inheritDoc}
     */
    public function updateCommission(string $limitId, float $commission): bool
    {
        // Validar que la comisión esté en rango válido
        if ($commission < 0 || $commission > 100) {
            throw new \InvalidArgumentException('La comisión debe estar entre 0 y 100');
        }

        return $this->model->where('id', $limitId)
            ->update(['commission' => $commission]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function updateCommissionByGameRoomSeller(string $gameId, string $roomId, string $sellerId, float $commission): bool
    {
        // Validar que la comisión esté en rango válido
        if ($commission < 0 || $commission > 100) {
            throw new \InvalidArgumentException('La comisión debe estar entre 0 y 100');
        }

        return $this->model->where('game', $gameId)
            ->where('room', $roomId)
            ->where('seller', $sellerId)
            ->update(['commission' => $commission]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteLimit(string $limitId): bool
    {
        return $this->model->where('id', $limitId)->delete() > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteBySeller(string $sellerId): int
    {
        return $this->model->where('seller', $sellerId)->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function deleteByRoom(string $roomId): int
    {
        return $this->model->where('room', $roomId)->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function getCommissionStatsBySeller(string $sellerId): array
    {
        $limits = $this->getBySeller($sellerId);

        if ($limits->isEmpty()) {
            return [
                'total_limits' => 0,
                'average_commission' => 0,
                'max_commission' => 0,
                'min_commission' => 0,
                'total_rooms' => 0,
                'total_games' => 0
            ];
        }

        return [
            'total_limits' => $limits->count(),
            'average_commission' => round($limits->avg('commission'), 2),
            'max_commission' => $limits->max('commission'),
            'min_commission' => $limits->min('commission'),
            'total_rooms' => $limits->unique('room')->count(),
            'total_games' => $limits->unique('game')->count()
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getCommissionStatsByRoom(string $roomId): array
    {
        $limits = $this->getByRoom($roomId);

        if ($limits->isEmpty()) {
            return [
                'total_limits' => 0,
                'average_commission' => 0,
                'max_commission' => 0,
                'min_commission' => 0,
                'total_sellers' => 0,
                'total_games' => 0
            ];
        }

        return [
            'total_limits' => $limits->count(),
            'average_commission' => round($limits->avg('commission'), 2),
            'max_commission' => $limits->max('commission'),
            'min_commission' => $limits->min('commission'),
            'total_sellers' => $limits->unique('seller')->count(),
            'total_games' => $limits->unique('game')->count()
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getSellersWithRoomAccess(string $roomId): Collection
    {
        return $this->model->with('Seller')
            ->where('room', $roomId)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getRoomsForSeller(string $sellerId): Collection
    {
        return $this->model->with('Room')
            ->where('seller', $sellerId)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function hasSellerRoomAccess(string $sellerId, string $roomId): bool
    {
        return $this->model->where('seller', $sellerId)
            ->where('room', $roomId)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getByCommissionRange(float $minCommission, float $maxCommission): Collection
    {
        return $this->model->whereBetween('commission', [$minCommission, $maxCommission])->get();
    }

    /**
     * {@inheritDoc}
     */
    public function createBulkLimits(array $limitsData): Collection
    {
        $limits = collect();

        foreach ($limitsData as $limitData) {
            $limit = $this->createOrUpdateLimit($limitData);
            $limits->push($limit);
        }

        return $limits;
    }

    /**
     * {@inheritDoc}
     */
    public function validateLimitData(array $data, ?string $excludeId = null): array
    {
        $errors = [];

        // Validar campos requeridos
        $requiredFields = ['game', 'room', 'seller'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[$field] = "El campo {$field} es requerido";
            }
        }

        // Validar comisión si está presente
        if (isset($data['commission'])) {
            $commission = (float) $data['commission'];
            if ($commission < 0 || $commission > 100) {
                $errors['commission'] = 'La comisión debe estar entre 0 y 100';
            }
        }

        // Validar unicidad de la combinación
        if (isset($data['game']) && isset($data['room']) && isset($data['seller'])) {
            $existing = $this->model->where('game', $data['game'])
                ->where('room', $data['room'])
                ->where('seller', $data['seller']);

            if ($excludeId) {
                $existing->where('id', '!=', $excludeId);
            }

            if ($existing->exists()) {
                $errors['combination'] = 'Ya existe un límite para esta combinación de juego, sala y vendedor';
            }
        }

        return $errors;
    }

    /**
     * {@inheritDoc}
     */
    public function getAverageCommissionByGame(string $gameId): float
    {
        return (float) $this->model->where('game', $gameId)->avg('commission') ?? 0.0;
    }

    /**
     * {@inheritDoc}
     */
    public function getSellerDistributionByRoom(): array
    {
        return $this->model->select('rooms.name', DB::raw('COUNT(DISTINCT sellers_room_limits.seller) as seller_count'))
            ->leftJoin('rooms', 'sellers_room_limits.room', '=', 'rooms.id')
            ->groupBy('rooms.id', 'rooms.name')
            ->pluck('seller_count', 'rooms.name')
            ->toArray();
    }
}