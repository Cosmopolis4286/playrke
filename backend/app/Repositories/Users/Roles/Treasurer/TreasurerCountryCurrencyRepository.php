<?php

namespace App\Repositories\Users\Roles\Treasurer;

use App\Models\Users\Roles\Treasurer\TreasurerCountryCurrency;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Users\Roles\Treasurer\Contracts\TreasurerCountryCurrencyRepositoryContract;

/**
 * Repositorio para TreasurerCountryCurrency.
 */
class TreasurerCountryCurrencyRepository extends BaseRepository implements TreasurerCountryCurrencyRepositoryContract
{
    public function __construct(TreasurerCountryCurrency $model)
    {
        parent::__construct($model);
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