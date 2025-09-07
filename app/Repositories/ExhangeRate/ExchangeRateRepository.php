<?php

namespace App\Repositories\ExchangeRate;

use App\Models\ExchangeRate\ExchangeRate;

class ExchangeRateRepository
{
    /**
     * Obtener el último valor de la tasa de cambio por moneda.
     *
     * @param int $currencyId
     * @return float|null
     */
    public function getLastValueByCurrency(int $currencyId): ?float
    {
        return ExchangeRate::where('currency_id', $currencyId)
            ->latest()
            ->value('value');
    }

    /**
     * Obtener un valor actual de la tasa de cambio por moneda.
     *
     * @param int $currencyId
     * @return float|null
     */
    public function getValue(int $currencyId): ?float
    {
        return ExchangeRate::where('currency_id', $currencyId)
            ->value('value');
    }

    /**
     * Crear un nuevo registro de tasa de cambio.
     *
     * @param array $data
     * @return ExchangeRate
     */
    public function create(array $data): ExchangeRate
    {
        return ExchangeRate::create($data);
    }

    /**
     * Obtener todas las tasas de cambio registradas para una moneda.
     *
     * @param int $currencyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllByCurrency(int $currencyId)
    {
        return ExchangeRate::where('currency_id', $currencyId)
            ->orderByDesc('created_at')
            ->get();
    }
}
