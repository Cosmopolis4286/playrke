<?php

namespace App\Repositories\Roles\Treasurer;

use App\Models\Roles\Treasurer\TreasurerCountryCurrency;
use App\Repositories\BaseRepository;
use App\Contracts\Roles\Treasurer\TreasurerCountryCurrencyRepositoryInterface;

/**
 * Repositorio para TreasurerCountryCurrency.
 */
class TreasurerCountryCurrencyRepository extends BaseRepository implements TreasurerCountryCurrencyRepositoryInterface
{
    public function __construct(TreasurerCountryCurrency $model)
    {
        $this->model = $model;
    }

    public function getByTreasurer(string $treasurerId)
    {
        return $this->model->where('treasurer_id', $treasurerId)->get();
    }

    public function getByCountry(string $countryId)
    {
        return $this->model->where('country_id', $countryId)->get();
    }

    public function getByCurrency(string $currencyId)
    {
        return $this->model->where('currency_id', $currencyId)->get();
    }
}
