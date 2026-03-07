<?php

namespace App\Repositories\Roles\Seller;

use App\Repositories\BaseRepository;
use App\Contracts\Roles\Seller\SellerCurrencyRepositoryInterface;
use App\Models\Pivots\Roles\Seller\SellerCurrency;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repositorio para la gestión de relaciones vendedor-moneda.
 *
 * Implementa las operaciones específicas para el modelo SellerCurrency,
 * incluyendo gestión de estados, validaciones y estadísticas de relaciones.
 */
class SellerCurrencyRepository extends BaseRepository implements SellerCurrencyRepositoryInterface
{
    /**
     * Constructor del repositorio.
     *
     * @param SellerCurrency $model Instancia del modelo SellerCurrency
     */
    public function __construct(SellerCurrency $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function getBySeller(string $sellerId): Collection
    {
        return $this->model->where('seller_id', $sellerId)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByCurrency(string $currencyId): Collection
    {
        return $this->model->where('currency_id', $currencyId)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveRelations(): Collection
    {
        return $this->getByStatus(true);
    }

    /**
     * {@inheritDoc}
     */
    public function getInactiveRelations(): Collection
    {
        return $this->getByStatus(false);
    }

    /**
     * {@inheritDoc}
     */
    public function getBySellerAndCurrency(string $sellerId, string $currencyId): ?SellerCurrency
    {
        return $this->model->where('seller_id', $sellerId)
            ->where('currency_id', $currencyId)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function existsRelation(string $sellerId, string $currencyId): bool
    {
        return $this->model->where('seller_id', $sellerId)
            ->where('currency_id', $currencyId)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function createRelation(array $data): SellerCurrency
    {
        // Validar datos antes de crear
        $errors = $this->validateRelationData($data);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Datos de relación inválidos: ' . json_encode($errors));
        }

        return $this->model->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function updateRelation(string $relationId, array $data): bool
    {
        // Validar datos antes de actualizar
        $errors = $this->validateRelationData($data, $relationId);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Datos de relación inválidos: ' . json_encode($errors));
        }

        return $this->model->where('id', $relationId)->update($data) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function updateStatus(string $relationId, bool $status): bool
    {
        return $this->model->where('id', $relationId)
            ->update(['status' => $status]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function activateRelation(string $relationId): bool
    {
        return $this->updateStatus($relationId, true);
    }

    /**
     * {@inheritDoc}
     */
    public function deactivateRelation(string $relationId): bool
    {
        return $this->updateStatus($relationId, false);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteRelation(string $relationId): bool
    {
        return $this->model->where('id', $relationId)->delete() > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getCountCurrency(string $sellerId): int
    {
        return $this->model->select('currency_id')
            ->where('seller_id', $sellerId)
            ->whereNotNull('currency_id')
            ->distinct()
            ->count();
    }

    /**
     * {@inheritDoc}
     */
    public function getAvailableCurrencies(string $sellerId, bool $activeOnly = true): array
    {
        $query = $this->model->select('currency_id')
            ->where('seller_id', $sellerId)
            ->distinct();

        if ($activeOnly) {
            $query->where('status', true);
        }

        return $query->pluck('currency_id')->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function getValue(string $sellerId, string $currencyId, string $field): mixed
    {
        return $this->model->where('seller_id', $sellerId)
            ->where('currency_id', $currencyId)
            ->value($field);
    }

    /**
     * {@inheritDoc}
     */
    public function getCountCountryCurrency(string $sellerId): int
    {
        return $this->model->where('seller_id', $sellerId)->count();
    }

    /**
     * {@inheritDoc}
     */
    public function getRelationStatistics(): array
    {
        $total = $this->model->count();
        $active = $this->model->where('status', true)->count();
        $inactive = $this->model->where('status', false)->count();
        $uniqueSellers = $this->model->distinct('seller_id')->count();
        $uniqueCurrencies = $this->model->distinct('currency_id')->count();
        $avgLevel = $this->model->avg('level');

        return [
            'total_relations' => $total,
            'active_relations' => $active,
            'inactive_relations' => $inactive,
            'active_percentage' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
            'unique_sellers' => $uniqueSellers,
            'unique_currencies' => $uniqueCurrencies,
            'average_level' => round($avgLevel ?? 0, 2),
            'sellers_with_multiple_currencies' => $this->getSellersWithMultipleCurrencies(),
            'most_popular_currency' => $this->getMostPopularCurrency()
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getRelationsWithData(bool $activeOnly = true): Collection
    {
        $query = $this->model->with(['seller', 'currency']);

        if ($activeOnly) {
            $query->where('status', true);
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function createBulkRelations(array $relationsData): Collection
    {
        $relations = collect();

        foreach ($relationsData as $relationData) {
            $relation = $this->createRelation($relationData);
            $relations->push($relation);
        }

        return $relations;
    }

    /**
     * {@inheritDoc}
     */
    public function getForSelect(?string $sellerId = null, bool $activeOnly = true): array
    {
        $query = $this->model->with(['seller', 'currency']);

        if ($sellerId) {
            $query->where('seller_id', $sellerId);
        }

        if ($activeOnly) {
            $query->where('status', true);
        }

        $relations = $query->get();
        $result = [];

        foreach ($relations as $relation) {
            $sellerName = $relation->seller?->name ?? 'Sin vendedor';
            $currencyCode = $relation->currency?->code ?? 'Sin moneda';

            $result[$relation->id] = "{$sellerName} - {$currencyCode}";
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function validateRelationData(array $data, ?string $excludeId = null): array
    {
        $errors = [];

        // Validar campos requeridos
        if (empty($data['seller_id'])) {
            $errors['seller_id'] = 'El vendedor es requerido';
        }

        if (empty($data['currency_id'])) {
            $errors['currency_id'] = 'La moneda es requerida';
        }

        // Validar nivel si está presente
        if (isset($data['level'])) {
            $level = (int) $data['level'];
            if ($level < 1 || $level > 10) {
                $errors['level'] = 'El nivel debe estar entre 1 y 10';
            }
        }

        // Validar unicidad de la combinación vendedor-moneda
        if (!empty($data['seller_id']) && !empty($data['currency_id'])) {
            $existingQuery = $this->model->where('seller_id', $data['seller_id'])
                ->where('currency_id', $data['currency_id']);

            if ($excludeId) {
                $existingQuery->where('id', '!=', $excludeId);
            }

            if ($existingQuery->exists()) {
                $errors['combination'] = 'Ya existe una relación para este vendedor y moneda';
            }
        }

        return $errors;
    }

    /**
     * {@inheritDoc}
     */
    public function bulkUpdateStatus(array $relationIds, bool $status): int
    {
        return $this->model->whereIn('id', $relationIds)
            ->update(['status' => $status]);
    }

    /**
     * {@inheritDoc}
     */
    public function getSellersByCurrency(string $currencyId, bool $activeOnly = true): Collection
    {
        $query = $this->model->with('seller')
            ->where('currency_id', $currencyId)
            ->distinct('seller_id');

        if ($activeOnly) {
            $query->where('status', true);
        }

        return $query->get()->pluck('seller_id')->filter();
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrencyDistribution(): array
    {
        return $this->model->select('currencies.code', DB::raw('COUNT(sellers_currencies.id) as count'))
            ->leftJoin('currencies', 'sellers_currencies.currency_id', '=', 'currencies.id')
            ->groupBy('currencies.id', 'currencies.code')
            ->pluck('count', 'currencies.code')
            ->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function getOperationLevel(string $sellerId, string $currencyId): ?int
    {
        return $this->model->where('seller_id', $sellerId)
            ->where('currency_id', $currencyId)
            ->value('level');
    }

    /**
     * {@inheritDoc}
     */
    public function updateOperationLevel(string $sellerId, string $currencyId, int $level): bool
    {
        if ($level < 1 || $level > 10) {
            throw new \InvalidArgumentException('El nivel debe estar entre 1 y 10');
        }

        return $this->model->where('seller_id', $sellerId)
            ->where('currency_id', $currencyId)
            ->update(['level' => $level]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getBySellerCountryAndCurrency(string $sellerId, string $currencyId): ?SellerCurrency
    {
        return $this->model->where('seller_id', $sellerId)
            ->where('currency_id', $currencyId)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getAvailableCountries(string $sellerId, bool $activeOnly = true): array
    {
        $query = $this->model->select('currencies.country_id')
            ->join('currencies', 'sellers_currencies.currency_id', '=', 'currencies.id')
            ->where('seller_id', $sellerId)
            ->whereNotNull('currencies.country_id')
            ->distinct();

        if ($activeOnly) {
            $query->where('sellers_currencies.status', true);
        }

        return $query->pluck('currencies.country_id')->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function getCountryDistribution(): array
    {
        return $this->model->select('countries.name', DB::raw('COUNT(sellers_currencies.id) as count'))
            ->leftJoin('currencies', 'sellers_currencies.currency_id', '=', 'currencies.id')
            ->leftJoin('countries', 'currencies.country_id', '=', 'countries.id')
            ->groupBy('countries.id', 'countries.name')
            ->pluck('count', 'countries.name')
            ->toArray();
    }

    /**
     * Obtener vendedores con múltiples monedas.
     *
     * @return int
     */
    private function getSellersWithMultipleCurrencies(): int
    {
        return $this->model->select('seller_id')
            ->groupBy('seller_id')
            ->havingRaw('COUNT(DISTINCT currency_id) > 1')
            ->count();
    }

    /**
     * Obtener la moneda más popular.
     *
     * @return string|null
     */
    private function getMostPopularCurrency(): ?string
    {
        $result = $this->model->select('currencies.code', DB::raw('COUNT(*) as count'))
            ->leftJoin('currencies', 'sellers_currencies.currency_id', '=', 'currencies.id')
            ->groupBy('currencies.id', 'currencies.code')
            ->orderBy('count', 'desc')
            ->first();

        return $result?->code;
    }
}
