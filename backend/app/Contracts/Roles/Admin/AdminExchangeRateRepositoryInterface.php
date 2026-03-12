<?php

namespace App\Contracts\Roles\Admin;

use App\Contracts\ExchangeRate\RoleExchangeRateRepositoryInterface;
use App\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repository de tasas de cambio de administradores
 */
interface AdminExchangeRateRepositoryInterface extends RoleExchangeRateRepositoryInterface
{
    /**
     * Obtiene todas las tasas de cambio
     *
     * @return Collection
     */
    public function getAllExchangeRates(): Collection;

    /**
     * Obtiene tasas de cambio por moneda
     *
     * @param mixed $currencyId ID de la moneda
     * @return Collection
     */
    public function getExchangeRatesByCurrency($currencyId): Collection;

    /**
     * Obtiene valor específico de tasa por moneda
     *
     * @param mixed $currencyId ID de la moneda
     * @param string $field Campo a obtener
     * @return mixed
     */
    public function getCurrencyValue($currencyId, string $field = 'amount');

    /**
     * Crea o actualiza una tasa de cambio
     *
     * @param mixed $currencyId ID de la moneda
     * @param float $amount Valor de la tasa
     * @param float $percentage Porcentaje
     * @return bool
     */
    public function updateOrCreateExchangeRate($currencyId, float $amount, float $percentage): bool;

    /**
     * Obtiene la tasa más reciente por moneda
     *
     * @param mixed $currencyId ID de la moneda
     * @return ExchangeRate|null
     */
    public function getLatestRate($currencyId): ?ExchangeRate;

    /**
     * Obtiene el historial completo de tasas por moneda
     *
     * @param mixed $currencyId ID de la moneda
     * @return Collection
     */
    public function getHistoryByCurrency($currencyId): Collection;

    /**
     * Obtiene las tasas de cambio disponibles para administradores
     *
     * @return Collection
     */
    public function getAvailableExchangeRates(): Collection;

    /**
     * Obtiene los códigos ISO de las monedas disponibles
     *
     * @return array Lista de códigos ISO
     */
    public function getAvailableCurrenciesIsoCodes(): array;
}
