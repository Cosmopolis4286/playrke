<?php

namespace App\Contracts\Roles\Treasurer;

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
