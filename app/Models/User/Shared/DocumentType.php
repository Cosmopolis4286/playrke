<?php

namespace App\Models\User\Shared;

use App\Models\Countries\Country;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $table = "document_type";
    protected $fillable = ['value', 'description', 'country', 'status'];

    public function Country()
    {
        return $this->belongsTo(Country::class, 'country');
    }

    public static function getValue($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getDocumentTypeByCountry($country)
    {
        return self::where('country', $country)->where('status', 1)->get();
    }
}
