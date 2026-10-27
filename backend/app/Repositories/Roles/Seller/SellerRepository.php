<?php

namespace App\Repositories\Roles\Seller;

use App\Contracts\Roles\Seller\SellerRepositoryInterface;
use App\Models\Roles\Seller;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Repositorio para la gestión de vendedores.
 *
 * Implementa las operaciones específicas para el modelo Seller,
 * incluyendo gestión de estados, comisiones, búsquedas y estadísticas.
 */
class SellerRepository extends BaseRepository implements SellerRepositoryInterface
{
    /**
     * Constructor del repositorio.
     *
     * @param Seller $model Instancia del modelo Seller
     */
    public function __construct(Seller $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function activateSeller(string $sellerId) : bool
    {
        return $this->updateStatus($sellerId, true);
    }

    /**
     * {@inheritDoc}
     */
    public function bulkUpdateStatus(array $sellerIds, bool $status) : int
    {
        return $this->model->whereIn('id', $sellerIds)
            ->update(['status' => $status]);
    }

    /**
     * {@inheritDoc}
     */
    public function createBulkSellers(array $sellersData) : Collection
    {
        $sellers = collect();

        foreach ($sellersData as $sellerData) {
            $seller = $this->createSeller($sellerData);
            $sellers->push($seller);
        }

        return $sellers;
    }

    /**
     * {@inheritDoc}
     */
    public function createSeller(array $data) : Seller
    {
        // Validar datos antes de crear
        $errors = $this->validateSellerData($data);
        if (! empty($errors)) {
            throw new InvalidArgumentException('Datos de vendedor inválidos: ' . json_encode($errors));
        }

        return $this->model->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function deactivateSeller(string $sellerId) : bool
    {
        return $this->updateStatus($sellerId, false);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteSeller(string $sellerId) : bool
    {
        return $this->model->where('id', $sellerId)->delete() > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function existsByPhone(string $phone, ?string $excludeId = null) : bool
    {
        $query = $this->model->where('phone', $phone);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveSellers() : Collection
    {
        return $this->getByStatus(true);
    }

    /**
     * {@inheritDoc}
     */
    public function getAverageCommission() : float
    {
        return (float) $this->model->where('status', true)->avg('commission') ?? 0.0;
    }

    /**
     * {@inheritDoc}
     */
    public function getByCommissionRange(float $minCommission, float $maxCommission) : Collection
    {
        return $this->model->whereBetween('commission', [$minCommission, $maxCommission])->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByCountry(string $countryId) : Collection
    {
        return $this->model->where('country', $countryId)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByCurrency(string $currencyId) : Collection
    {
        return $this->model->where('currency', $currencyId)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByManager(string $managerUserId) : Collection
    {
        return $this->model->where('manager_user_id', $managerUserId)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByName(string $name) : Collection
    {
        return $this->model->where('name', 'LIKE', "%{$name}%")->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByPhone(string $phone) : ?Seller
    {
        return $this->model->where('phone', $phone)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getByUserId(string $userId) : ?Seller
    {
        return $this->model->where('user_id', $userId)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getCountryDistribution() : array
    {
        return $this->model->select('countries.name', DB::raw('COUNT(sellers.id) as count'))
            ->leftJoin('countries', 'sellers.country', '=', 'countries.id')
            ->groupBy('countries.id', 'countries.name')
            ->pluck('count', 'countries.name')
            ->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrencyByIdId(string $sellerId) : ?string
    {
        return $this->model->where('id', $sellerId)->value('currency');
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrencyDistribution() : array
    {
        return $this->model->select('currencies.code', DB::raw('COUNT(sellers.id) as count'))
            ->leftJoin('currencies', 'sellers.currency', '=', 'currencies.id')
            ->groupBy('currencies.id', 'currencies.code')
            ->pluck('count', 'currencies.code')
            ->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function getForSelect(bool $activeOnly = true, ?string $countryFilter = null) : array
    {
        $query = $this->model->select('id', 'name', 'country');

        if ($activeOnly) {
            $query->where('status', true);
        }

        if ($countryFilter) {
            $query->where('country', $countryFilter);
        }

        return $query->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function getInactiveSellers() : Collection
    {
        return $this->getByStatus(false);
    }

    /**
     * {@inheritDoc}
     */
    public function getIncompleteData() : Collection
    {
        return $this->model->where(function($query) {
            $query->whereNull('country')
                ->orWhereNull('currency')
                ->orWhereNull('commerce_id');
        })->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrderedByCommission(string $direction = 'desc', bool $activeOnly = false) : Collection
    {
        $query = $this->model->orderBy('commission', $direction);

        if ($activeOnly) {
            $query->where('status', true);
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getSalesCountBySeller(string $sellerId) : int
    {
        return DB::table('bingo_room_users')
            ->where('seller', $sellerId)
            ->count();
    }

    /**
     * {@inheritDoc}
     */
    public function getSellerStatistics() : array
    {
        $total = $this->model->count();
        $active = $this->model->where('status', true)->count();
        $inactive = $this->model->where('status', false)->count();
        $avgCommission = $this->model->avg('commission');
        $maxCommission = $this->model->max('commission');
        $minCommission = $this->model->min('commission');
        $withCommerce = $this->model->whereNotNull('commerce_id')->count();
        $withPhone = $this->model->whereNotNull('phone')->count();

        return [
            'total_sellers' => $total,
            'active_sellers' => $active,
            'inactive_sellers' => $inactive,
            'active_percentage' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
            'average_commission' => round($avgCommission ?? 0, 2),
            'max_commission' => $maxCommission ?? 0,
            'min_commission' => $minCommission ?? 0,
            'with_commerce' => $withCommerce,
            'with_phone' => $withPhone,
            'commerce_percentage' => $total > 0 ? round(($withCommerce / $total) * 100, 2) : 0,
            'phone_percentage' => $total > 0 ? round(($withPhone / $total) * 100, 2) : 0,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getSellersWithSalesStats(bool $activeOnly = true) : Collection
    {
        $query = $this->model->select('sellers.*')
            ->selectRaw('COUNT(bingo_room_users.id) as sales_count')
            ->selectRaw('SUM(bingo_room_users.total_amount) as total_sales_amount')
            ->leftJoin('bingo_room_users', 'sellers.id', '=', 'bingo_room_users.seller')
            ->groupBy('sellers.id')
            ->orderBy('sales_count', 'desc');

        if ($activeOnly) {
            $query->where('sellers.status', true);
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getTopSellers(int $limit = 10) : Collection
    {
        return $this->model->select('sellers.*')
            ->selectRaw('COUNT(bingo_room_users.id) as sales_count')
            ->leftJoin('bingo_room_users', 'sellers.id', '=', 'bingo_room_users.seller')
            ->groupBy('sellers.id')
            ->orderBy('sales_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function isSellerInUse(string $sellerId) : bool
    {
        // Verificar si el vendedor tiene lotes de cartones vendidos registrados.
        // bingo_room_users no tiene columna de vendedor (solo room_id/user_id/role),
        // así que la única fuente real de "vendedor en uso" es bingo_card_lots.
        return DB::table('bingo_card_lots')
            ->where('seller_user_id', $sellerId)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function updateCommission(string $sellerId, float $commission) : bool
    {
        // Validar que la comisión esté en rango válido
        if ($commission < 0 || $commission > 100) {
            throw new InvalidArgumentException('La comisión debe estar entre 0 y 100');
        }

        return $this->model->where('id', $sellerId)
            ->update(['commission' => $commission]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function updateSeller(string $sellerId, array $data) : bool
    {
        // Validar datos antes de actualizar
        $errors = $this->validateSellerData($data, $sellerId);
        if (! empty($errors)) {
            throw new InvalidArgumentException('Datos de vendedor inválidos: ' . json_encode($errors));
        }

        return $this->model->where('id', $sellerId)->update($data) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function updateStatus(string $sellerId, bool $status) : bool
    {
        return $this->model->where('id', $sellerId)
            ->update(['status' => $status]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function validateSellerData(array $data, ?string $excludeId = null) : array
    {
        $errors = [];

        // Validar nombre requerido
        if (isset($data['name']) && empty(trim($data['name']))) {
            $errors['name'] = 'El nombre del vendedor es requerido';
        }

        // Validar teléfono único si está presente
        if (isset($data['phone']) && ! empty($data['phone'])) {
            if ($this->existsByPhone($data['phone'], $excludeId)) {
                $errors['phone'] = 'Ya existe un vendedor con este teléfono';
            }
        }

        // Validar comisión
        if (isset($data['commission'])) {
            $commission = (float) $data['commission'];
            if ($commission < 0 || $commission > 100) {
                $errors['commission'] = 'La comisión debe estar entre 0 y 100';
            }
        }

        // Validar que el usuario no esté duplicado
        if (isset($data['user'])) {
            $existingUser = $this->model->where('user', $data['user']);
            if ($excludeId) {
                $existingUser->where('id', '!=', $excludeId);
            }
            if ($existingUser->exists()) {
                $errors['user'] = 'Ya existe un vendedor asociado a este usuario';
            }
        }

        return $errors;
    }
}
