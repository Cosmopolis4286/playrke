<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    protected $table = "settings";
    protected $fillable = ['description', 'status'];

    public static function getStatus($description)
    {
        return self::where('description', $description)->value('status');
    }
}
