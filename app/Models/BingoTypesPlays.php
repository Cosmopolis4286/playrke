<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoTypesPlays extends Model
{
    protected $table = 'bingo_types_plays';
    protected $fillable = ['name', 'user', 'boxes', 'status'];

    public static function getById($id, $fields = ['*'])
    {
        return self::where('id', $id)->select($fields)->first();
    }

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }
    
    public static function existsBingoPlay($column, $value, $user = '')
    {
        return self::where($column, $value)
            ->when($user, function ($query) use ($user) {
                $query->where('user', $user);
            })->exists();
    }
}
