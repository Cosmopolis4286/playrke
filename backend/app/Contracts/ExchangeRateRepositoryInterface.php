<?php

namespace App\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de tipos de cambio.
 *
 * Define los métodos específicos para gestionar los tipos de cambio del sistema,
 * incluyendo operaciones de consulta, creación, actualización y estadísticas.
 */
interface ExchangeRateRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Obtener tipo de cambio por moneda.
     *
     * @param string $currencyId ID de la moneda
     * @return ExchangeRate|null El tipo de cambio encontrado o null
     */
    public function getByCurrency(string $currencyId): ?ExchangeRate;

    /**
     * Obtener todos los tipos de cambio con sus monedas.
     *
     * @return Collection Colección de tipos de cambio con datos de moneda
     */
    public function getAllWithCurrency(): Collection;

    /**
     * Obtener tipos de cambio por rango de valores.
     *
     * @param float $minValue Valor mínimo
     * @param float $maxValue Valor máximo
     * @return Collection Tipos de cambio en el rango especificado
     */
    public function getByValueRange(float $minValue, float $maxValue): Collection;

    /**
     * Verificar si existe un tipo de cambio para una moneda.
     *
     * @param string $currencyId ID de la moneda
     * @return bool True si existe, false en caso contrario
     */
    public function existsForCurrency(string $currencyId): bool;

    /**
     * Crear un nuevo tipo de cambio.
     *
     * @param string $currencyId ID de la moneda
     * @param float $value Valor del tipo de cambio
     * @return ExchangeRate El tipo de cambio creado
     */
    public function createExchangeRate(string $currencyId, float $value): ExchangeRate;

    /**
     * Actualizar el valor de un tipo de cambio.
     *
     * @param string $exchangeRateId ID del tipo de cambio
     * @param float $value Nuevo valor
     * @return bool True si se actualizó correctamente
     */
    public function updateValue(string $exchangeRateId, float $value): bool;

    /**
     * Actualizar tipo de cambio por moneda.
     *
     * @param string $currencyId ID de la moneda
     * @param float $value Nuevo valor
     * @return bool True si se actualizó correctamente
     */
    public function updateByCurrency(string $currencyId, float $value): bool;

    /**
     * Eliminar un tipo de cambio.
     *
     * @param string $exchangeRateId ID del tipo de cambio
     * @return bool True si se eliminó correctamente
     */
    public function deleteExchangeRate(string $exchangeRateId): bool;

    /**
     * Eliminar tipo de cambio por moneda.
     *
     * @param string $currencyId ID de la moneda
     * @return bool True si se eliminó correctamente
     */
    public function deleteByCurrency(string $currencyId): bool;

    /**
     * Obtener estadísticas de tipos de cambio.
     *
     * @return array<string, mixed> Array con estadísticas
     */
    public function getExchangeRateStatistics(): array;

    /**
     * Obtener tipos de cambio ordenados por valor.
     *
     * @param string $direction Dirección del ordenamiento (asc|desc)
     * @return Collection Tipos de cambio ordenados por valor
     */
    public function getOrderedByValue(string $direction = 'desc'): Collection;

    /**
     * Obtener los tipos de cambio más altos.
     *
     * @param int $limit Límite de resultados (por defecto 10)
     * @return Collection Tipos de cambio con valores más altos
     */
    public function getHighestRates(int $limit = 10): Collection;

    /**
     * Obtener los tipos de cambio más bajos.
     *
     * @param int $limit Límite de resultados (por defecto 10)
     * @return Collection Tipos de cambio con valores más bajos
     */
    public function getLowestRates(int $limit = 10): Collection;

    /**
     * Crear tipos de cambio por lotes.
     *
     * @param array $exchangeRatesData Array de datos de tipos de cambio
     * @return Collection Tipos de cambio creados
     */
    public function createBulkExchangeRates(array $exchangeRatesData): Collection;

    /**
     * Obtener tipos de cambio para uso en selects/formularios.
     *
     * @return array<string, string> Array asociativo [id => "moneda - valor"]
     */
    public function getForSelect(): array;

    /**
     * Validar datos de tipo de cambio antes de crear/actualizar.
     *
     * @param array $data Datos a validar
     * @param string|null $excludeId ID a excluir en validaciones únicas
     * @return array<string, string> Array de errores de validación
     */
    public function validateExchangeRateData(array $data, ?string $excludeId = null): array;

    /**
     * Obtener valor de tipo de cambio por moneda.
     *
     * @param string $currencyId ID de la moneda
     * @return float|null Valor del tipo de cambio o null si no existe
     */
    public function getValueByCurrency(string $currencyId): ?float;

    /**
     * Actualización masiva de valores.
     *
     * @param array $updates Array de actualizaciones [currency_id => value]
     * @return int Número de registros actualizados
     */
    public function bulkUpdateValues(array $updates): int;

    /**
     * Obtener tipos de cambio recientes.
     *
     * @param int $days Número de días hacia atrás (por defecto 7)
     * @return Collection Tipos de cambio actualizados recientemente
     */
    public function getRecentlyUpdated(int $days = 7): Collection;

    /**
     * Verificar si un valor de tipo de cambio es válido.
     *
     * @param float $value Valor a validar
     * @return bool True si es válido
     */
    public function isValidValue(float $value): bool;

    /**
     * Obtener monedas sin tipo de cambio definido.
     *
     * @return Collection Monedas que no tienen tipo de cambio
     */
    public function getCurrenciesWithoutExchangeRate(): Collection;

    /**
     * Calcular conversión entre monedas.
     *
     * @param string $fromCurrencyId ID de la moneda origen
     * @param string $toCurrencyId ID de la moneda destino
     * @param float $amount Cantidad a convertir
     * @return float|null Cantidad convertida o null si no es posible
     */
    public function convertCurrency(string $fromCurrencyId, string $toCurrencyId, float $amount): ?float;

    /**
     * Crear o actualizar tipo de cambio (upsert).
     *
     * @param string $currencyId ID de la moneda
     * @param float $value Valor del tipo de cambio
     * @return ExchangeRate El tipo de cambio creado o actualizado
     */
    public function createOrUpdateByCurrency(string $currencyId, float $value): ExchangeRate;
}
