<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Awards extends Model
{
    protected $table = 'awards';
    protected $fillable = ['code', 'kit', 'num_items', 'price', 'description'];

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }
}
