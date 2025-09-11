<?php

namespace App\Models\Wallets;

use App\Models\Currencies\Currency;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Model;
use BradieTilley\Snowflake\Snowflake;

class Wallet extends Model
{
    protected $table = 'wallets';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'currency_id',
        'user_id',
        'balance',
        'is_active',
    ];

    /* ========================
     | Boot method
     |======================== */
    protected static function boot()
    {
        parent::boot();

        // Generar ID Snowflake automáticamente
        static::creating(function ($wallet) {
            if (!$wallet->id) {
                $wallet->id = (new Snowflake())->id();
            }
        });
    }

    /* ========================
     | Relaciones
     |======================== */

    /**
     * Relación con el usuario propietario
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación con la moneda (currency)
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /* ========================
     | Helpers
     |======================== */

    /**
     * Obtener un valor específico de la wallet de un usuario
     */
    public static function getValue(string $userId, string $column)
    {
        return self::where('user_id', $userId)->value($column);
    }
}
