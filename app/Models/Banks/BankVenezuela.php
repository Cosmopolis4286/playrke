<?php

namespace App\Models\Banks;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankVenezuela extends Model
{
    protected $table = 'banks_venezuela';

    protected $fillable = [
        'name',       // Nombre corto o código
        'code',       // Código del banco
        'full_name',  // Nombre completo del banco
    ];

    /**
     * Relación con los pagos móviles asociados a este banco.
     *
     * @return HasMany
     */
    public function pagoMoviles(): HasMany
    {
        return $this->hasMany(PagoMovil::class, 'bank_id');
    }
}
