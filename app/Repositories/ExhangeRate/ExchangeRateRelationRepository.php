<?php

namespace App\Repositories\ExchangeRate;

use App\Models\ExchangeRate\CommerceExchangeRate;

class CommerceExchangeRateRepository
{
    /**
     * Obtener las monedas permitidas para un usuario en un comercio.
     *
     * @param int $userId
     * @param int $commerceId
     * @return array<int>
     */
    public function allowedCurrenciesByCommerce(int $userId, int $commerceId): array
    {
        return CommerceExchangeRate::where('user_id', $userId)
            ->where('commerce_id', $commerceId)
            ->distinct()
            ->pluck('currency_id')
            ->toArray();
    }

    /**
     * Crear una nueva relación entre usuario, comercio y moneda.
     *
     * @param array $data
     * @return CommerceExchangeRate
     */
    public function create(array $data): CommerceExchangeRate
    {
        return CommerceExchangeRate::create($data);
    }

    /**
     * Obtener todas las relaciones activas de un comercio.
     *
     * @param int $commerceId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveByCommerce(int $commerceId)
    {
        return CommerceExchangeRate::where('commerce_id', $commerceId)
            ->where('status', 1)
            ->get();
    }

    /**
     * Verificar si un usuario tiene acceso a una moneda en un comercio.
     *
     * @param int $userId
     * @param int $commerceId
     * @param int $currencyId
     * @return bool
     */
    public function hasAccessToCurrency(int $userId, int $commerceId, int $currencyId): bool
    {
        return CommerceExchangeRate::where('user_id', $userId)
            ->where('commerce_id', $commerceId)
            ->where('currency_id', $currencyId)
            ->exists();
    }
}
