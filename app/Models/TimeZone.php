<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeZone extends Model
{
    protected $table = "time_zones";
    protected $fillable = ['name', 'country', 'status', 'created_at', 'updated_at'];

    public function Country()
    {
        return $this->belongsTo(Country::class, 'country');
    }

    public static function getById($id)
    {
        return self::where('id', $id)->where('status', true)->value('name');
    }
}
