<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameModes extends Model
{
    protected $table = "game_modes";
    protected $fillable = ['id', 'name'];

    public static function getByName($name)
    {
        return self::where('name', $name)->first();
    }
}
