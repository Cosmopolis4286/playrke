<?php

namespace App\Services\Roles\Admin\Commerce;

use App\DTOs\Roles\Admin\Commerce\AdminCommerceContextDTO;
use App\Models\Roles\Commerce;
use App\Services\Platform\PlatformConfigService;
use App\Traits\HashedTableDataTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\LazyCollection;

final class AdminCommerceContextService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly \App\Contracts\CountryRepositoryInterface $countries,
        private readonly \App\Contracts\CurrencyRepositoryInterface $currencies,
        private readonly \App\Contracts\GameRepositoryInterface $games,
        private readonly \App\Contracts\TimeZoneRepositoryInterface $timeZones,
        private readonly PlatformConfigService $platformConfig,
    ) {}

    /**
     * Resolver contexto para comercios.
     *
     * @return AdminCommerceContextDTO
     */
    public function commerceContext(): AdminCommerceContextDTO
    {
        $commerceData = $this->resolveCommerces();

        return new AdminCommerceContextDTO(
            commerces: $commerceData['commerces'],
            pagination: $commerceData['pagination'],
            countries: $this->countries->getActive(['id', 'name', 'iso_code_2']),
            currencies: $this->currencies->getActive(['id', 'iso_code', 'symbol', 'name', 'country_id']),
            games: $this->games->getActive(['id', 'name']),
            timeZones: $this->timeZones->getActive(['id', 'name', 'utc', 'country_id']),
            defaultCommerceId: $this->platformConfig->getDefaultCommerceId()
        );
    }

    /**
     * Query base con filtros aplicados — compartida entre resolveCommerces()
     * (paginado, para la tabla) y exportForCsv() (cursor completo, para
     * CSV). Se construye directo sobre el modelo (no vía
     * CommerceRepositoryInterface::paginateWithRelations()) porque ese
     * repositorio solo soporta condiciones de igualdad/operador simple, no
     * LIKE ni whereHas sobre relaciones anidadas — mismo criterio que
     * AdminPlayerContextService::buildFilteredQuery().
     */
    public function buildFilteredQuery(array $filters = []): Builder
    {
        $query = Commerce::query()->with(['country:id,name', 'user:id,email']);

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', "%{$term}%")
                    ->orWhere('phone', 'LIKE', "%{$term}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('email', 'LIKE', "%{$term}%"));
            });
        }

        if (!empty($filters['country_name'])) {
            $countryName = $filters['country_name'];
            $query->whereHas('country', fn ($q) => $q->where('name', $countryName));
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    /**
     * $filters acepta: search (nombre/teléfono/email), country_name, status.
     * Antes se ignoraban por completo — siempre se traía la página 1 de 15
     * sin filtrar (mismo problema que tenía AdminPlayerContextService antes
     * de corregirse).
     */
    public function resolveCommerces(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $labels  = ['Nombre', 'País', 'Teléfono', 'Email', 'Status'];
        $rowKeys = ['name', 'country.name', 'phone', 'User.email', 'status_badge'];

        $source = $this->buildFilteredQuery($filters)->paginate($perPage, ['*'], 'page', $page);

        $collection = $source->getCollection();

        $pagination = [
            'total'        => $source->total(),
            'per_page'     => $source->perPage(),
            'current_page' => $source->currentPage(),
            'last_page'    => $source->lastPage(),
            'from'         => $source->firstItem(),
            'to'           => $source->lastItem(),
        ];

        $commerces = $this->processCollectionTableData($collection, $labels, $rowKeys);

        // real_id: necesario para el botón "Ver ficha" (mismo motivo que
        // AdminPlayerContextService::resolvePlayers() expone real_id).
        $commercesList = $collection->values();
        $commerces['rows'] = collect($commerces['rows'])->map(function (array $row, int $index) use ($commercesList) {
            $row['real_id'] = $commercesList->get($index)?->id;
            return $row;
        })->all();

        return ['commerces' => $commerces, 'pagination' => $pagination];
    }

    /**
     * Cursor sobre TODOS los comercios que coincidan con los filtros (sin
     * paginar) — mismo patrón que AdminPlayerContextService::exportForCsv().
     *
     * @return LazyCollection<int, Commerce>
     */
    public function exportForCsv(array $filters = []): LazyCollection
    {
        return $this->buildFilteredQuery($filters)->cursor();
    }
}
