<?php

namespace App\Repositories\Coins;

use App\Models\Coins\Coin;

class CoinRepository
{
    /**
     * Obtener moneda por ID.
     *
     * @param int $coinId
     * @param array $fields
     * @return Coin|null
     */
    public static function getById(int $coinId, array $fields = ['*'])
    {
        return Coin::select($fields)->find($coinId);
    }

    /**
     * Obtener moneda por código ISO.
     *
     * @param string $isoCode
     * @param array $fields
     * @return Coin|null
     */
    public static function getByISOCode(string $isoCode, array $fields = ['*'])
    {
        return Coin::select($fields)->where('iso_code', $isoCode)->first();
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
        return Coin::select($fields)->where('status', $status[0], $status[1])->get();
    }

    /**
     * Obtener el valor de un campo por ID de moneda.
     *
     * @param int $coinId
     * @param string $field
     * @return mixed
     */
    public static function getValue(int $coinId, string $field)
    {
        return Coin::where('id', $coinId)->value($field);
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
        return Coin::where('iso_code', $isoCode)->value($field);
    }

    /**
     * Obtener la moneda de un país.
     *
     * @param int $countryId
     * @param array $fields
     * @return Coin|null
     */
    public static function getCoinByCountry(int $countryId, array $fields = ['*'])
    {
        return Coin::select($fields)->where('country_id', $countryId)->first();
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
        return Coin::where('country_id', $countryId)->value($field);
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
        return Coin::where('symbol', $symbol)->value($field);
    }

    /**
     * Obtener ID de la moneda de un país.
     *
     * @param int $countryId
     * @return int|null
     */
    public static function getIdByCountry(int $countryId)
    {
        return Coin::where('country_id', $countryId)->value('id');
    }

    /**
     * Obtener varias monedas por ID.
     *
     * @param array $coinIds
     * @param array $fields
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getByIds(array $coinIds, array $fields = ['*'])
    {
        return Coin::select($fields)->whereIn('id', $coinIds)->get();
    }

    /**
     * Obtener IDs de monedas de varios países.
     *
     * @param array $countryIds
     * @return array
     */
    public static function getCoinsByCountry(array $countryIds)
    {
        return Coin::whereIn('country_id', $countryIds)->pluck('id')->toArray();
    }

    /**
     * Obtener países asociados a varias monedas.
     *
     * @param array $coinIds
     * @return array
     */
    public static function getCountriesByCoins(array $coinIds)
    {
        return Coin::whereIn('id', $coinIds)->pluck('country_id')->toArray();
    }

    /**
     * Obtener el método de pago de una moneda.
     *
     * @param int $coinId
     * @return string|null
     */
    public static function getPaymentMethodByCoin(int $coinId)
    {
        return Coin::where('id', $coinId)->value('payment_method');
    }

    /**
     * Listar todas las monedas activas.
     *
     * @param array $fields
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAvailableCoins(array $fields = ['*'])
    {
        return Coin::select($fields)->where('status', 1)->get();
    }
}
