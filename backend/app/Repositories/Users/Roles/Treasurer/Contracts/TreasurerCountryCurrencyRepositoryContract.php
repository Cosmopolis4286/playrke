<?php

namespace App\Repositories\Roles\Treasurer\Contracts;

use App\Contracts\BaseRepositoryInterface;

/**
 * Contrato para el repositorio de TreasurerCountryCurrency.
 */
interface TreasurerCountryCurrencyRepositoryInterface extends BaseRepositoryInterface
{
    public function getByTreasurer(string $treasurerId);
    public function getByCountry(string $countryId);
    public function getByCurrency(string $currencyId);
}