<?php

namespace App\Services\Roles\Admin\Subsidiary;

use App\DTOs\Roles\Admin\Subsidiary\AdminSubsidiaryContextDTO;
use App\Traits\HashedTableDataTrait;
use Illuminate\Pagination\LengthAwarePaginator;

final class AdminSubsidiaryContextService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly \App\Contracts\Roles\Subsidiary\SubsidiaryRepositoryInterface $subsidiaries,
        private readonly \App\Contracts\Roles\Commerce\CommerceRepositoryInterface $commerces,
        private readonly \App\Contracts\CountryRepositoryInterface $countries,
        private readonly \App\Contracts\CurrencyRepositoryInterface $currencies,
    ) {}

    /**
     * Resolver contexto para sucursales.
     *
     * @return AdminSubsidiaryContextDTO
     */
    public function subsidiaryContext(): AdminSubsidiaryContextDTO
    {
        $subsidiaryData = $this->resolveSubsidiaries();

        return new AdminSubsidiaryContextDTO(
            subsidiaries: $subsidiaryData['subsidiaries'],
            pagination: $subsidiaryData['pagination'],
            commerces: $this->commerces->getByStatus('ACTIVE', ['id', 'name']),
            countries: $this->countries->getActive(['id', 'name', 'iso_code_2']),
            currencies: $this->currencies->getActive(['id', 'iso_code', 'symbol', 'name', 'country_id']),
        );
    }

    /**
     * Resolver listado de sucursales.
     *
     * @return array [subsidiaries, pagination]
     */
    public function resolveSubsidiaries(): array
    {
        $labels  = ['Nombre', 'Comercio', 'País', 'Teléfono', 'Email', 'Status'];
        $rowKeys = ['name', 'commerce.name', 'country.name', 'phone', 'user.email', 'status_badge'];

        $source = $this->subsidiaries->paginateWithRelations(
            perPage: 15,
            page: 1,
            relations: ['commerce:id,name', 'country:id,name', 'user:id,email'],
            conditions: [],
            fields: ['id', 'commerce_id', 'country_id', 'user_id', 'name', 'phone', 'status']
        );

        $collection = $source instanceof LengthAwarePaginator
            ? $source->getCollection()
            : collect($source);

        $pagination = $source instanceof LengthAwarePaginator
            ? [
                'total'        => $source->total(),
                'per_page'     => $source->perPage(),
                'current_page' => $source->currentPage(),
                'last_page'    => $source->lastPage(),
                'from'         => $source->firstItem(),
                'to'           => $source->lastItem(),
            ]
            : null;

        $subsidiaries = $this->processCollectionTableData($collection, $labels, $rowKeys);

        return ['subsidiaries' => $subsidiaries, 'pagination' => $pagination];
    }
}
