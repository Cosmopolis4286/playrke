<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    public const TYPES = [
        ['id' => 0, 'description' => 'Privada'],
        ['id' => 1, 'description' => 'Pública'],
    ];

    public static function getRoomTypes()
    {
        return self::TYPES;
    }
}
