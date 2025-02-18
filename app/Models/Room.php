<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    public const TYPES = [
        ['id' => 1, 'description' => 'Privada'],
        ['id' => 2, 'description' => 'Pública'],
    ];

    public static function getRoomTypes($excludeId = null)
    {
        if ($excludeId) {
            return array_filter(self::TYPES, function ($type) use ($excludeId) {
                return $type['id'] !== $excludeId;
            });
        }

        return self::TYPES;
    }
}
