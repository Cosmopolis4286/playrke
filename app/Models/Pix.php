<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pix extends Model
{
    protected $table = "pix";
    protected $fillable = ['bank', 'pix_key', 'pix_key_value', 'user'];

    public function Bank()
    {
        return $this->belongsTo(Banks_BRA::class, 'bank', 'code');
    }

    public function ChavePix()
    {
        return $this->belongsTo(PixKeys::class, 'pix_key');
    }

    public static function getValuePixKey($pixKey)
    {
        return self::where('pix_key_value', $pixKey)->first();
    }
}
