<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameModes extends Model
{
    protected $table = "game_modes";
    protected $fillable = ['id', 'description'];

    public static function getByName($description)
    {
        return self::where('description', $description)->first();
    }
}
