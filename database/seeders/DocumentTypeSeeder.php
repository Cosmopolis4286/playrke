<?php

namespace App\Models\User\Shared;

use App\Models\Countries\Country;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $table = "document_types"; // convención plural
    protected $fillable = ['value', 'description', 'country_id', 'status'];

    /**
     * Relación: un tipo de documento pertenece a un país.
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * Obtener un valor específico de un documento por ID.
     */
    public static function getValueById(int $id, string $column)
    {
        return self::where('id', $id)->value($column);
    }

    /**
     * Obtener los tipos de documento activos de un país.
     */
    public static function getActiveByCountry(int $countryId)
    {
        return self::where('country_id', $countryId)
            ->where('status', 1)
            ->get();
    }
}
