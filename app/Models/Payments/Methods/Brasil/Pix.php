<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pix extends Model
{
    protected $table = "pix";
    protected $fillable = ['name', 'bank', 'pix_key', 'pix_key_value', 'user'];
    protected $appends = ['chave_pix'];

    public const TYPES = [
        ['id' => 1, 'value' => 'CPF'],
        ['id' => 2, 'value' => 'Celular'],
        ['id' => 3, 'value' => 'Email'],
        ['id' => 4, 'value' => 'Chave aleatória'],
        ['id' => 5, 'value' => 'CPNJ']
    ];

    public function Bank()
    {
        return $this->belongsTo(BanksBrasil::class, 'bank');
    }

    public function getChavePixAttribute()
    {
        $pixKey = collect(self::TYPES)->firstWhere('id', $this->pix_key);
        return $pixKey['value'] ?? 'Desconocido';
    }

    public static function getPixKeys($excludeId = null)
    {
        if ($excludeId) {
            return array_filter(self::TYPES, function ($type) use ($excludeId) {
                return $type['id'] !== $excludeId;
            });
        }

        return self::TYPES;
    }

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValuePixKey($pixKey)
    {
        return self::where('pix_key_value', $pixKey)->first();
    }

    public static function existsPixKey($pixKey, $pixKeyVal, $user = false)
    {
        return self::where([['pix_key', $pixKey], ['pix_key_value', $pixKeyVal]])
            ->when($user, fn($query) => $query->where('user', '<>', $user))->exists();
    }
}
