<?php

namespace App\Models\User\Shared;

use App\Models\Countries\Country;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $table = "document_types"; // convención plural

    protected $fillable = [
        'value',
        'description',
        'country_id',
        'status'
    ];

    /**
     * Relación: un tipo de documento pertenece a un país.
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}
