<?php

namespace App\Repositories\User\Shared;

use App\Models\User\Shared\DocumentType;

class DocumentTypeRepository
{
    /**
     * Obtener un valor específico de un documento por ID.
     *
     * @param int $id
     * @param string $column
     * @return mixed
     */
    public function getValueById(int $id, string $column)
    {
        return DocumentType::where('id', $id)->value($column);
    }

    /**
     * Obtener los tipos de documento activos de un país.
     *
     * @param int $countryId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveByCountry(int $countryId)
    {
        return DocumentType::where('country_id', $countryId)
            ->where('status', 1)
            ->get();
    }

    /**
     * Crear o actualizar un tipo de documento.
     *
     * @param array $attributes
     * @param array $values
     * @return \App\Models\User\Shared\DocumentType
     */
    public function updateOrCreate(array $attributes, array $values = [])
    {
        return DocumentType::updateOrCreate($attributes, $values);
    }

    /**
     * Listar todos los tipos de documento activos.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllActive()
    {
        return DocumentType::where('status', 1)->get();
    }
}
