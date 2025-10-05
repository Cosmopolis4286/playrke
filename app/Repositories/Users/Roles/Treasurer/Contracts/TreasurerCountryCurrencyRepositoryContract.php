<?php

namespace App\Repositories\Users\Roles\Treasurer\Contracts;

use App\Repositories\Base\Contracts\BaseRepositoryContract;

/**
 * Contrato para el repositorio de TreasurerCountryCurrency.
 */
interface TreasurerCountryCurrencyRepositoryContract extends BaseRepositoryContract
{
    public function getByTreasurer(string $treasurerId);
    public function getByCountry(string $countryId);
    public function getByCurrency(string $currencyId);
}