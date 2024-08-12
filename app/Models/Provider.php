<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $table = "providers";
    protected $fillable = ['name', 'pvr_type', 'brand', 'product', 'link_route', 'token', 'security_key', 'status'];

    public static function getById($provider, $fields = ['*'])
    {
        return self::select($fields)->where('id', $provider)->first();
    }

    public static function getValue($provider, $value)
    {
        return self::where('id', $provider)->value($value);
    }
}
