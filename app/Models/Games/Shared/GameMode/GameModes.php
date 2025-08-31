<?php

namespace App\Models\Games\Shared\GameMode;

use Illuminate\Database\Eloquent\Model;

class GameModes extends Model
{
    protected $table = "game_modes";
    protected $fillable = ['description'];

    public static function getByName($description)
    {
        return self::where('description', $description)->first();
    }
}