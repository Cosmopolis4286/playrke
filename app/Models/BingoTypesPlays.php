<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoTypesPlays extends Model
{
    protected $table = 'bingo_types_plays';
    protected $fillable = ['type_play', 'name', 'user', 'boxes', 'status'];

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getAllByUser($user, $fields = ['*'], $status = null)
    {
        return self::select($fields)->where('user', $user)
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })->get();
    }

    public static function existsBingoPlay($column, $value, $user)
    {
        return self::where($column, $value)->where('user', $user)->exists();
    }
}
