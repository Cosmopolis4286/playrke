<?php

namespace App\Contracts;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de monedas
 * Define métodos específicos para operaciones con Currency
 */
interface CurrencyRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Verificar si existe una moneda por código ISO
     *
     * @param string $isoCode Código ISO
     */
    public function existsByIsoCode(string $isoCode) : bool;

    /**
     * Buscar moneda por código ISO
     *
     * @param string $isoCode Código ISO de la moneda
     * @param array  $columns Columnas a seleccionar
     */
    public function findByIsoCode(string $isoCode, array $columns = ['*']) : ?Currency;

    /**
     * Obtener monedas por múltiples códigos ISO
     *
     * @param array $isoCodes Array de códigos ISO
     * @param array $columns  Columnas a seleccionar
     *
     * @return Collection<Currency>
     */
    public function findByMultipleIsoCodes(array $isoCodes, array $columns = ['*']) : Collection;

    /**
     * Obtener monedas activas
     *
     * @param array $columns Columnas a seleccionar
     *
     * @return Collection<Currency>
     */
    public function getActive(array $columns = ['*']) : Collection;

    /**
     * Obtener monedas ordenadas por nombre
     *
     * @param array $columns Columnas a seleccionar
     *
     * @return Collection<Currency>
     */
    public function getAllOrderedByName(array $columns = ['*']) : Collection;

    /**
     * Buscar monedas por país
     *
     * @param string $countryId ID del país
     * @param array  $columns   Columnas a seleccionar
     *
     * @return Collection<Currency>
     */
    public function getByCountry(string $countryId, array $columns = ['*']) : Collection;

    /**
     * Obtener monedas por decimales específicos
     *
     * @param int   $decimalPlaces Cantidad de decimales
     * @param array $columns       Columnas a seleccionar
     *
     * @return Collection<Currency>
     */
    public function getByDecimalPlaces(int $decimalPlaces, array $columns = ['*']) : Collection;

    /**
     * Obtener monedas por tipo
     *
     * @param string $type    Tipo de moneda (FIAT/CRYPTO/TOKEN)
     * @param array  $columns Columnas a seleccionar
     *
     * @return Collection<Currency>
     */
    public function getByType(string $type, array $columns = ['*']) : Collection;

    /**
     * Obtener IDs de países a partir de un array de IDs de monedas
     *
     * @param array $currencyIds IDs de monedas
     *
     * @return array IDs de países
     */
    public function getCountriesByCurrencies(array $currencyIds) : array;

    /**
     * Obtener monedas CRYPTO
     *
     * @param array $columns Columnas a seleccionar
     *
     * @return Collection<Currency>
     */
    public function getCrypto(array $columns = ['*']) : Collection;

    /**
     * Obtener monedas FIAT
     *
     * @param array $columns Columnas a seleccionar
     *
     * @return Collection<Currency>
     */
    public function getFiat(array $columns = ['*']) : Collection;

    /**
     * Obtener monedas inactivas
     *
     * @param array $columns Columnas a seleccionar
     *
     * @return Collection<Currency>
     */
    public function getInactive(array $columns = ['*']) : Collection;

    /**
     * Obtener monedas TOKEN
     *
     * @param array $columns Columnas a seleccionar
     *
     * @return Collection<Currency>
     */
    public function getTokens(array $columns = ['*']) : Collection;

    /**
     * Obtener un valor específico de la moneda asociada a un país.
     *
     * @param int|string $countryId ID del país
     * @param string     $field     Campo a retornar (ej. 'id', 'symbol')
     *
     * @return mixed|null
     */
    public function getValueByCountry($countryId, string $field) : mixed;

    /**
     * Obtener valor por código ISO (método legacy)
     *
     * @param string $isoCode Código ISO
     * @param string $field   Campo a obtener
     */
    public function getValueByISOCode(string $isoCode, string $field) : mixed;

    /**
     * Obtener monedas con tasa de cambio
     *
     * @param array $columns Columnas a seleccionar
     *
     * @return Collection<Currency>
     */
    public function getWithExchangeRate(array $columns = ['*']) : Collection;

    /**
     * Buscar monedas por nombre
     *
     * @param string $searchTerm Término de búsqueda
     * @param array  $columns    Columnas a seleccionar
     *
     * @return Collection<Currency>
     */
    public function searchByName(string $searchTerm, array $columns = ['*']) : Collection;
}
