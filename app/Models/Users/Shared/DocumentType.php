<?php

namespace App\Models\Users\Shared;

use App\Models\Countries\Country;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $table = "document_types"; // convención plural

    protected $fillable = [
        'code',
        'description',
        'country_id',
        'is_active'
    ];

    /**
     * Relación: un tipo de documento pertenece a un país.
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}
