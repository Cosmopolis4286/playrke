<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    protected $table = "settings";
    protected $fillable = ['param', 'param_val', 'status'];

    public static function getStatus($param)
    {
        return self::where('param', $param)->value('status');
    }
}
