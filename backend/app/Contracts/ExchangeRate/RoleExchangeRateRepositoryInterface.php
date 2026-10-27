<?php

namespace App\Contracts\ExchangeRate;

use App\DTOs\ExchangeRate\ExchangeRateDTO;
use Illuminate\Database\Eloquent\Model;

interface RoleExchangeRateRepositoryInterface
{
    /**
     * Crea, actualiza o restaura una tasa de cambio por entidad de rol y moneda.
     *
     * - Si existe y está soft-deleted → se restaura y actualiza
     * - Si existe y está activa → se actualiza
     * - Si no existe → se crea
     */
    public function createOrRestoreByCurrency(int $roleEntityId, ExchangeRateDTO $dto) : Model;
}
