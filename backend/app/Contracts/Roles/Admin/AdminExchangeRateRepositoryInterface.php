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
     * Tasas actuales de varias monedas a la vez (currency_id => amount) — usado
     * por ExchangeRateUpdaterService::updateForRole() para resolver TODAS las
     * tasas vigentes en una sola query en vez de una por moneda dentro del
     * loop de actualización masiva.
     *
     * @param array<int, string> $currencyIds
     * @return array<string, float>
     */
    public function getCurrentRatesBatch(array $currencyIds): array;

    /**
     * Crea o actualiza una tasa de cambio.
     *
     * $userId/$source explícitos (antes usaba Auth::id() internamente,
     * ver ver AdminExchangeRateRepository) — en el flujo automático
     * (HandleExchangeRatesCommand, cronjob) no hay sesión HTTP autenticada,
     * así que Auth::id() era null contra una columna user_id NOT NULL: el
     * insert fallaba en silencio (capturado por el try/catch del caller) y
     * el cronjob nunca actualizaba ninguna tasa con éxito.
     *
     * @param mixed $currencyId ID de la moneda
     * @param float $amount Valor de la tasa
     * @param float $percentage Porcentaje
     * @param int $userId Usuario real que originó la actualización (dueño
     *                     de la preferencia auto_update_enabled en el caso
     *                     del cronjob, o el usuario autenticado en el flujo
     *                     manual/botón "Consultar")
     * @param string $source ExchangeRate::SOURCE_USER o SOURCE_SYSTEM
     * @return bool
     */
    public function updateOrCreateExchangeRate($currencyId, float $amount, float $percentage, int $userId, string $source = 'user'): bool;

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
