<?php

namespace App\Repositories\Countries;

use App\Models\Countries\Country;

class CountryRepository
{
    /**
     * Obtener un país por su ID.
     *
     * @param int $id
     * @param array $fields
     * @return Country|null
     */
    public function getById(int $id, array $fields = ['*'])
    {
        return Country::select($fields)->find($id);
    }

    /**
     * Obtener múltiples países por sus IDs.
     *
     * @param array $ids
     * @param array $fields
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCountriesById(array $ids, array $fields = ['*'])
    {
        return Country::select($fields)->whereIn('id', $ids)->get();
    }

    /**
     * Obtener un valor específico de un país.
     *
     * @param int $id
     * @param string $column
     * @return mixed
     */
    public function getValue(int $id, string $column)
    {
        return Country::where('id', $id)->value($column);
    }

    /**
     * Listar todos los países activos (is_active = true).
     *
     * @param array $fields
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllAvailableCountries(array $fields = ['*'])
    {
        return Country::select($fields)->where('is_active', true)->get();
    }

    /**
     * Obtener un país por su código ISO (2 o 3 caracteres).
     *
     * @param string $isoCode
     * @param int $length
     * @return Country|null
     */
    public function getCountryByIsoCode(string $isoCode, int $length = 2)
    {
        return Country::where("iso_code_{$length}", strtoupper($isoCode))->first();
    }

    /**
     * Obtener países activos (is_active = true) filtrados por IDs.
     *
     * @param array $ids
     * @param array $fields
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCountries(array $ids, array $fields = ['*'])
    {
        return Country::select($fields)
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->get();
    }
}
