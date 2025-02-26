<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    public const TYPES = [
        'PV' => 'Privada',
        'PB' => 'Pública'
    ];

    public static function getRoomTypes($excludeId = null)
    {
        if ($excludeId) {
            return array_filter(self::TYPES, function ($id) use ($excludeId) {
                return $id !== $excludeId;
            }, ARRAY_FILTER_USE_BOTH);
        }

        return self::TYPES;
    }
}
