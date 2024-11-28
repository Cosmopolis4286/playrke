<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoPlayTypes extends Model
{
    protected $table = 'bingo_play_types';
    protected $fillable = ['code', 'description', 'boxes', 'status', 'created_at', 'updated_at'];

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getByCode($code, $fields = ['*'])
    {
        return self::select($fields)->where('code', $code)->first();
    }

    public static function getValueByCode($code, $value)
    {
        return self::where('code', $code)->value($value);
    }
}
