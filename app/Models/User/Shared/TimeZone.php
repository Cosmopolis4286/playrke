<?php

namespace App\Models\User\Shared;

use App\Models\Countries\Country;
use Illuminate\Database\Eloquent\Model;

class TimeZone extends Model
{
    protected $table = "time_zones";
    protected $fillable = ['name', 'utc', 'country_id', 'status', 'created_at', 'updated_at'];

    public function Country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValueById($id, $value = 'name')
    {
        return self::where('id', $id)->value($value);
    }
}
