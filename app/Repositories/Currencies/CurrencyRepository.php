<?php

namespace App\Repositories\Currencies;

use App\Models\Currencies\Currency;

class CurrencyRepository
{
    /**
     * Obtener moneda por ID.
     *
     * @param int $currencyId
     * @param array $fields
     * @return Currency|null
     */
    public static function getById(int $currencyId, array $fields = ['*'])
    {
        return Currency::select($fields)->find($currencyId);
    }

    /**
     * Obtener moneda por código ISO.
     *
     * @param string $isoCode
     * @param array $fields
     * @return Currency|null
     */
    public static function getByISOCode(string $isoCode, array $fields = ['*'])
    {
        return Currency::select($fields)->where('iso_code', $isoCode)->first();
    }

    /**
     * Listar monedas filtrando por estado.
     *
     * @param array $status [operador, valor] ej: ['>=', 0]
     * @param array $fields
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getByStatus(array $status = ['>=', 0], array $fields = ['*'])
    {
        return Currency::select($fields)->where('status', $status[0], $status[1])->get();
    }

    /**
     * Obtener el valor de un campo por ID de moneda.
     *
     * @param int $currencyId
     * @param string $field
     * @return mixed
     */
    public static function getValue(int $currencyId, string $field)
    {
        return Currency::where('id', $currencyId)->value($field);
    }

    /**
     * Obtener el valor de un campo por ISO Code.
     *
     * @param string $isoCode
     * @param string $field
     * @return mixed
     */
    public static function getValueByISOCode(string $isoCode, string $field)
    {
        return Currency::where('iso_code', $isoCode)->value($field);
    }

    /**
     * Obtener la moneda de un país.
     *
     * @param int $countryId
     * @param array $fields
     * @return Currency|null
     */
    public static function getCurrencyByCountry(int $countryId, array $fields = ['*'])
    {
        return Currency::select($fields)->where('country_id', $countryId)->first();
    }

    /**
     * Obtener valor de un campo según país.
     *
     * @param int $countryId
     * @param string $field
     * @return mixed
     */
    public static function getValueByCountry(int $countryId, string $field)
    {
        return Currency::where('country_id', $countryId)->value($field);
    }

    /**
     * Obtener valor de un campo por símbolo de moneda.
     *
     * @param string $symbol
     * @param string $field
     * @return mixed
     */
    public static function getValueBySymbol(string $symbol, string $field)
    {
        return Currency::where('symbol', $symbol)->value($field);
    }

    /**
     * Obtener ID de la moneda de un país.
     *
     * @param int $countryId
     * @return int|null
     */
    public static function getIdByCountry(int $countryId)
    {
        return Currency::where('country_id', $countryId)->value('id');
    }

    /**
     * Obtener varias monedas por ID.
     *
     * @param array $currencyIds
     * @param array $fields
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getByIds(array $currencyIds, array $fields = ['*'])
    {
        return Currency::select($fields)->whereIn('id', $currencyIds)->get();
    }

    /**
     * Obtener IDs de monedas de varios países.
     *
     * @param array $countryIds
     * @return array
     */
    public static function getCurrenciesByCountry(array $countryIds)
    {
        return Currency::whereIn('country_id', $countryIds)->pluck('id')->toArray();
    }

    /**
     * Obtener países asociados a varias monedas.
     *
     * @param array $currencyIds
     * @return array
     */
    public static function getCountriesByCurrencies(array $currencyIds)
    {
        return Currency::whereIn('id', $currencyIds)->pluck('country_id')->toArray();
    }

    /**
     * Obtener el método de pago de una moneda.
     *
     * @param int $currencyId
     * @return string|null
     */
    public static function getPaymentMethodByCurrency(int $currencyId)
    {
        return Currency::where('id', $currencyId)->value('payment_method');
    }

    /**
     * Listar todas las monedas activas.
     *
     * @param array $fields
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAvailableCurrencies(array $fields = ['*'])
    {
        return Currency::select($fields)->where('status', 1)->get();
    }
}
