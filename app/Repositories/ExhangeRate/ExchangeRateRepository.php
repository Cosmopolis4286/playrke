<?php

namespace App\Repositories\ExchangeRate;

use App\Models\ExchangeRate\ExchangeRate;

class ExchangeRateRepository
{
    /**
     * Obtener el último valor de la tasa de cambio por moneda.
     *
     * @param int $coinId
     * @return float|null
     */
    public function getLastValueByCoin(int $coinId): ?float
    {
        return ExchangeRate::where('coin_id', $coinId)
            ->latest()
            ->value('value');
    }

    /**
     * Obtener un valor actual de la tasa de cambio por moneda.
     *
     * @param int $coinId
     * @return float|null
     */
    public function getValue(int $coinId): ?float
    {
        return ExchangeRate::where('coin_id', $coinId)
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
     * @param int $coinId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllByCoin(int $coinId)
    {
        return ExchangeRate::where('coin_id', $coinId)
            ->orderByDesc('created_at')
            ->get();
    }
}
