<?php

namespace App\Repositories;

use App\Models\Currency;
use App\Repositories\BaseRepository;
use App\Contracts\CurrencyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositorio para operaciones con monedas
 * Maneja todas las consultas complejas relacionadas con Currency
 */
class CurrencyRepository extends BaseRepository implements CurrencyRepositoryInterface
{
    public function __construct(Currency $model)
    {
        parent::__construct($model);
    }

    /**
     * Buscar moneda por código ISO
     *
     * @param string $isoCode Código ISO de la moneda
     * @param array $columns Columnas a seleccionar
     * @return Currency|null
     */
    public function findByIsoCode(string $isoCode, array $columns = ['*']): Collection
    {
        return $this->model->where('iso_code', strtoupper($isoCode))->first($columns);
    }

    /**
     * Obtener monedas por tipo
     *
     * @param string $type Tipo de moneda (FIAT/CRYPTO/TOKEN)
     * @param array $columns Columnas a seleccionar
     * @return Collection<Currency>
     */
    public function getByType(string $type, array $columns = ['*']): Collection
    {
        return $this->model->where('type', strtoupper($type))
            ->orderBy('name')
            ->get($columns);
    }

    /**
     * Obtener monedas activas
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Currency>
     */
    public function getActive(array $columns = ['*']): Collection
    {
        return $this->model->where('status', true)
            ->orderBy('name')
            ->get($columns);
    }

    /**
     * Obtener monedas inactivas
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Currency>
     */
    public function getInactive(array $columns = ['*']): Collection
    {
        return $this->model->where('status', false)
            ->orderBy('name')
            ->get($columns);
    }

    /**
     * Obtener monedas FIAT
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Currency>
     */
    public function getFiat(array $columns = ['*']): Collection
    {
        return $this->getByType('FIAT', $columns);
    }

    /**
     * Obtener monedas CRYPTO
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Currency>
     */
    public function getCrypto(array $columns = ['*']): Collection
    {
        return $this->getByType('CRYPTO', $columns);
    }

    /**
     * Obtener monedas TOKEN
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Currency>
     */
    public function getTokens(array $columns = ['*']): Collection
    {
        return $this->getByType('TOKEN', $columns);
    }

    /**
     * Buscar monedas por país
     *
     * @param string $countryId ID del país
     * @param array $columns Columnas a seleccionar
     * @return Collection<Currency>
     */
    public function getByCountry(string $countryId, array $columns = ['*']): Collection
    {
        return $this->model->where('country_id', $countryId)
            ->orderBy('name')
            ->get($columns);
    }

    /**
     * Buscar monedas por nombre
     *
     * @param string $searchTerm Término de búsqueda
     * @param array $columns Columnas a seleccionar
     * @return Collection<Currency>
     */
    public function searchByName(string $searchTerm, array $columns = ['*']): Collection
    {
        return $this->model->where(function ($query) use ($searchTerm) {
            $query->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('description', 'like', "%{$searchTerm}%")
                ->orWhere('iso_code', 'like', "%{$searchTerm}%");
        })->orderBy('name')->get($columns);
    }

    /**
     * Verificar si existe una moneda por código ISO
     *
     * @param string $isoCode Código ISO
     * @return bool
     */
    public function existsByIsoCode(string $isoCode): bool
    {
        return $this->model->where('iso_code', strtoupper($isoCode))->exists();
    }

    /**
     * Obtener monedas ordenadas por nombre
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Currency>
     */
    public function getAllOrderedByName(array $columns = ['*']): Collection
    {
        return $this->model->orderBy('name')->get($columns);
    }

    /**
     * Obtener monedas con tasa de cambio
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Currency>
     */
    public function getWithExchangeRate(array $columns = ['*']): Collection
    {
        return $this->model->whereHas('exchangeRate')
            ->with('exchangeRate')
            ->orderBy('name')
            ->get($columns);
    }

    /**
     * Obtener monedas por múltiples códigos ISO
     *
     * @param array $isoCodes Array de códigos ISO
     * @param array $columns Columnas a seleccionar
     * @return Collection<Currency>
     */
    public function findByMultipleIsoCodes(array $isoCodes, array $columns = ['*']): Collection
    {
        $isoCodes = array_map('strtoupper', $isoCodes);

        return $this->model->whereIn('iso_code', $isoCodes)
            ->orderBy('name')
            ->get($columns);
    }

    /**
     * Obtener monedas por decimales específicos
     *
     * @param int $decimalPlaces Cantidad de decimales
     * @param array $columns Columnas a seleccionar
     * @return Collection<Currency>
     */
    public function getByDecimalPlaces(int $decimalPlaces, array $columns = ['*']): Collection
    {
        return $this->model->where('decimal_places', $decimalPlaces)
            ->orderBy('name')
            ->get($columns);
    }

    /**
     * Obtener monedas activas por tipo
     *
     * @param string $type Tipo de moneda
     * @param array $columns Columnas a seleccionar
     * @return Collection<Currency>
     */
    public function getActiveByType(string $type, array $columns = ['*']): Collection
    {
        return $this->model->where('type', strtoupper($type))
            ->where('status', true)
            ->orderBy('name')
            ->get($columns);
    }

    /**
     * Obtener monedas con país asociado
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Currency>
     */
    public function getWithCountry(array $columns = ['*']): Collection
    {
        return $this->model->whereNotNull('country_id')
            ->with('country')
            ->orderBy('name')
            ->get($columns);
    }

    /**
     * Obtener monedas sin país asociado
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<Currency>
     */
    public function getWithoutCountry(array $columns = ['*']): Collection
    {
        return $this->model->whereNull('country_id')
            ->orderBy('name')
            ->get($columns);
    }

    /**
     * Contar monedas por tipo
     *
     * @return array Array con conteo por tipo
     */
    public function getCountByType(): array
    {
        $counts = $this->model->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        return [
            'FIAT' => $counts['FIAT'] ?? 0,
            'CRYPTO' => $counts['CRYPTO'] ?? 0,
            'TOKEN' => $counts['TOKEN'] ?? 0,
            'total' => array_sum($counts)
        ];
    }

    // =================================
    // Métodos legacy mantenidos temporalmente
    // =================================

    /**
     * Obtener moneda por código ISO (método legacy)
     *
     * @param string $isoCode Código ISO
     * @param array $fields Campos a seleccionar
     * @return Currency|null
     */
    public function getByISOCode(string $isoCode, array $fields = ['*']): ?Currency
    {
        return $this->findByIsoCode($isoCode, $fields);
    }

    /**
     * Obtener moneda por país (método legacy)
     *
     * @param int|string $countryId ID del país
     * @param array $fields Campos a seleccionar
     * @return Currency|null
     */
    public function getCurrencyByCountry($countryId, array $fields = ['*']): ?Currency
    {
        return $this->model->where('country_id', $countryId)->first($fields);
    }

    /**
     * Obtener monedas disponibles (método legacy)
     *
     * @param array $fields Campos a seleccionar
     * @return Collection<Currency>
     */
    public function getAvailableCurrencies(array $fields = ['*']): Collection
    {
        return $this->getActive($fields);
    }

    /**
     * Obtener valor por código ISO (método legacy)
     *
     * @param string $isoCode Código ISO
     * @param string $field Campo a obtener
     * @return mixed
     */
    public function getValueByISOCode(string $isoCode, string $field): mixed
    {
        return $this->model->where('iso_code', strtoupper($isoCode))->value($field);
    }

    /**
     * Obtener valor por país (método legacy)
     *
     * @param int|string $countryId ID del país
     * @param string $field Campo a obtener
     * @return mixed
     */
    public function getValueByCountry($countryId, string $field): mixed
    {
        return $this->model->where('country_id', $countryId)->value($field);
    }

    /**
     * Obtener valor por símbolo (método legacy)
     *
     * @param string $symbol Símbolo de la moneda
     * @param string $field Campo a obtener
     * @return mixed
     */
    public function getValueBySymbol(string $symbol, string $field): mixed
    {
        return $this->model->where('symbol', $symbol)->value($field);
    }

    /**
     * Obtener ID por país (método legacy)
     *
     * @param int|string $countryId ID del país
     * @return mixed
     */
    public function getIdByCountry($countryId): mixed
    {
        return $this->model->where('country_id', $countryId)->value('id');
    }

    /**
     * Obtener IDs de monedas por países (método legacy)
     *
     * @param array $countryIds Array de IDs de países
     * @return array
     */
    public function getCurrenciesByCountry(array $countryIds): array
    {
        return $this->model->whereIn('country_id', $countryIds)->pluck('id')->toArray();
    }

    /**
     * Obtener países por monedas (método legacy)
     *
     * @param array $currencyIds Array de IDs de monedas
     * @return array
     */
    public function getCountriesByCurrencies(array $currencyIds): array
    {
        return $this->model->whereIn('id', $currencyIds)->pluck('country_id')->toArray();
    }
}
