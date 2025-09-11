<?php

namespace App\Models\Games\Shared;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $table = "games";
    protected $fillable = ['name', 'status'];

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValueByName($name, $value = 'id')
    {
        return self::where('name', strtoupper($name))->value($value);
    }

    public static function getAvailableGames()
    {
        return self::where('status', 1)->get();
    }
}
