<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pix extends Model
{
    protected $table = "pix";
    protected $fillable = ['name', 'bank', 'pix_key', 'pix_key_value', 'user'];

    public function Bank()
    {
        return $this->belongsTo(Banks_BRA::class, 'bank');
    }

    public function ChavePix()
    {
        return $this->belongsTo(PixKeys::class, 'pix_key');
    }

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValuePixKey($pixKey)
    {
        return self::where('pix_key_value', $pixKey)->first();
    }

    public static function existsPixKey($pixKey, $pixKeyVal)
    {
        return self::where('pix_key', $pixKey)->where('pix_key_value', $pixKeyVal)->exists();
    }
}
