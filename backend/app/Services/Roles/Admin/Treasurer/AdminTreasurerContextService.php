<?php

namespace App\Services\Roles\Admin\Treasurer;

use App\DTOs\Roles\Admin\Treasurer\AdminTreasurerContextDTO;
use App\Traits\HashedTableDataTrait;
use Illuminate\Pagination\LengthAwarePaginator;

final class AdminTreasurerContextService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly \App\Contracts\Roles\Treasurer\TreasurerRepositoryInterface $treasurers,
        private readonly \App\Contracts\CountryRepositoryInterface $countries,
        private readonly \App\Contracts\CurrencyRepositoryInterface $currencies,
        private readonly \App\Contracts\GameRepositoryInterface $games,
        private readonly \App\Contracts\TimeZoneRepositoryInterface $timeZones
    ) {}

    /**
     * Resolver contexto para tesoreros.
     *
     * @return AdminTreasurerContextDTO
     */
    public function treasurerContext(): AdminTreasurerContextDTO
    {
        $treasurerData = $this->resolveTreasurers();

        return new AdminTreasurerContextDTO(
            treasurers: $treasurerData['treasurers'],
            pagination: $treasurerData['pagination'],
            countries: $this->countries->getActive(['id', 'name', 'iso_code_2']),
            currencies: $this->currencies->getActive(['id', 'iso_code', 'symbol', 'name', 'country_id']),
            games: $this->games->getActive(['id', 'name']),
            timeZones: $this->timeZones->getActive(['id', 'name', 'utc', 'country_id'])
        );
    }

    /**
     * Resolver listado de tesoreros.
     *
     * @return array [treasurers, pagination]
     */
    public function resolveTreasurers(): array
    {
        $labels  = ['Nombre', 'País', 'Teléfono', 'Email', 'Status'];
        $rowKeys = ['name', 'country.name', 'phone', 'user.email', 'status_badge'];

        $source = $this->treasurers->paginateWithRelations(
            perPage: 15,
            page: 1,
            relations: ['country:id,name', 'user:id,email'],
            conditions: [],
            fields: ['id', 'country_id', 'user_id', 'name', 'phone', 'status']
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

        $treasurers = $this->processCollectionTableData($collection, $labels, $rowKeys);

        return ['treasurers' => $treasurers, 'pagination' => $pagination];
    }
}
